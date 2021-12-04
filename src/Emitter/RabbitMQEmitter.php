<?php

/**
 * Scheduler implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=0);

namespace ServiceBus\Scheduler\Emitter;

use Amp\Promise;
use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Scheduler\Contract\EmitSchedulerOperation;
use ServiceBus\Scheduler\Contract\SchedulerOperationEmitted;
use ServiceBus\Scheduler\Data\NextScheduledOperation;
use ServiceBus\Scheduler\Data\ScheduledOperation;
use ServiceBus\Scheduler\Delivery\SchedulerDeliveryOptions;
use ServiceBus\Scheduler\Exceptions\EmitFailed;
use ServiceBus\Scheduler\ScheduledOperationId;
use ServiceBus\Scheduler\Store\Exceptions\ScheduledOperationNotFound;
use ServiceBus\Scheduler\Store\SchedulerStore;
use function Amp\call;
use function ServiceBus\Common\now;

final class RabbitMQEmitter implements SchedulerEmitter
{
    /**
     * @var SchedulerStore
     */
    private $store;

    public function __construct(SchedulerStore $store)
    {
        $this->store = $store;
    }

    public function emit(ScheduledOperationId $id, ServiceBusContext $context): Promise
    {
        return call(
            function () use ($id, $context): \Generator
            {
                try
                {
                    yield $this->store->extract($id, $this->createPostExtract($context));
                }
                catch (ScheduledOperationNotFound $exception)
                {
                    $context->logger()->throwable($exception);

                    yield $context->delivery(
                        new SchedulerOperationEmitted($id)
                    );
                }
                catch (\Throwable $throwable)
                {
                    throw new EmitFailed($throwable->getMessage(), (int) $throwable->getCode(), $throwable);
                }
            }
        );
    }

    public function emitNextOperation(?NextScheduledOperation $nextOperation, ServiceBusContext $context): Promise
    {
        return call(
            function () use ($nextOperation, $context): \Generator
            {
                try
                {
                    if ($nextOperation === null)
                    {
                        $context->logger()->debug('Next operation not specified');

                        return;
                    }

                    $delay = $this->calculateExecutionDelay($nextOperation);

                    /** Message will return after a specified time interval */
                    yield $context->delivery(
                        new EmitSchedulerOperation($nextOperation->id),
                        SchedulerDeliveryOptions::scheduledMessage($delay)
                    );

                    $context->logger()->debug(
                        'Scheduled operation with identifier "{scheduledOperationId}" will be executed after "{scheduledOperationDelay}" seconds',
                        [
                            'scheduledOperationId'    => $nextOperation->id->toString(),
                            'scheduledOperationDelay' => $delay / 1000,
                        ]
                    );
                }
                catch (\Throwable $throwable)
                {
                    throw new EmitFailed($throwable->getMessage(), (int) $throwable->getCode(), $throwable);
                }
            }
        );
    }

    /**
     * @psalm-return callable(?ScheduledOperation, ?NextScheduledOperation): \Generator
     */
    private function createPostExtract(ServiceBusContext $context): callable
    {
        return static function (?ScheduledOperation $operation, ?NextScheduledOperation $nextOperation) use ($context): \Generator
        {
            if ($operation !== null)
            {
                yield $context->delivery($operation->command);
                yield $context->delivery(new SchedulerOperationEmitted($operation->id, $nextOperation));

                $context->logger()->debug(
                    'The delayed "{messageClass}" command has been sent to the transport',
                    [
                        'messageClass'         => \get_class($operation->command),
                        'scheduledOperationId' => $operation->id->toString(),
                    ]
                );
            }
        };
    }

    /**
     * Calculate next execution delay.
     *
     * @psalm-return positive-int
     */
    private function calculateExecutionDelay(NextScheduledOperation $nextScheduledOperation): int
    {
        $executionDelay = $nextScheduledOperation->time->getTimestamp() - now()->getTimestamp();

        /**
         * @psalm-var positive-int $delay
         *
         * @noinspection OneTimeUseVariablesInspection
         * @noinspection PhpUnnecessaryLocalVariableInspection
         */
        $delay = (int) bcmul((string) $executionDelay, '1000');

        return $delay;
    }
}
