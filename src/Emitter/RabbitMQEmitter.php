<?php

/**
 * Scheduler implementation.
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Scheduler\Emitter;

use function Amp\call;
use function ServiceBus\Common\datetimeInstantiator;
use Amp\Promise;
use Psr\Log\LogLevel;
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

/**
 *
 */
final class RabbitMQEmitter implements SchedulerEmitter
{
    /** @var SchedulerStore */
    private $store;

    public function __construct(SchedulerStore $store)
    {
        $this->store = $store;
    }

    /**
     * {@inheritdoc}
     */
    public function emit(ScheduledOperationId $id, ServiceBusContext $context): Promise
    {
        /** @psalm-suppress InvalidArgument */
        return call(
            function (ScheduledOperationId $id) use ($context): \Generator
            {
                try
                {
                    yield $this->store->extract($id, $this->createPostExtract($context));
                }
                catch (ScheduledOperationNotFound $exception)
                {
                    $context->logContextThrowable($exception);

                    yield $context->delivery(
                        new SchedulerOperationEmitted($id)
                    );
                }
                catch (\Throwable $throwable)
                {
                    throw new EmitFailed($throwable->getMessage(), (int) $throwable->getCode(), $throwable);
                }
            },
            $id
        );
    }

    /**
     * {@inheritdoc}
     */
    public function emitNextOperation(?NextScheduledOperation $nextOperation, ServiceBusContext $context): Promise
    {
        /** @psalm-suppress InvalidArgument */
        return call(
            function (?NextScheduledOperation $nextOperation) use ($context): \Generator
            {
                try
                {
                    if (null === $nextOperation)
                    {
                        $context->logContextMessage('Next operation not specified', [], LogLevel::DEBUG);

                        return;
                    }

                    $id    = $nextOperation->id;
                    $delay = $this->calculateExecutionDelay($nextOperation);

                    /** Message will return after a specified time interval */
                    yield $context->delivery(
                        new  EmitSchedulerOperation($id),
                        SchedulerDeliveryOptions::scheduledMessage($context->traceId(), $delay)
                    );

                    $context->logContextMessage(
                        'Scheduled operation with identifier "{scheduledOperationId}" will be executed after "{scheduledOperationDelay}" seconds',
                        [
                            'scheduledOperationId'    => $id,
                            'scheduledOperationDelay' => $delay / 1000,
                        ]
                    );
                }
                catch (\Throwable $throwable)
                {
                    throw new EmitFailed($throwable->getMessage(), (int) $throwable->getCode(), $throwable);
                }
            },
            $nextOperation
        );
    }

    private function createPostExtract(ServiceBusContext $context): callable
    {
        return static function (?ScheduledOperation $operation, ?NextScheduledOperation $nextOperation) use ($context): void
        {
            if (null !== $operation)
            {
                $context->delivery($operation->command)->onResolve(
                    static function () use ($operation, $nextOperation, $context): \Generator
                    {
                        $context->logContextMessage(
                            'The delayed "{messageClass}" command has been sent to the transport',
                            [
                                'messageClass'         => \get_class($operation->command),
                                'scheduledOperationId' => $operation->id->toString(),
                            ]
                        );

                        yield $context->delivery(new SchedulerOperationEmitted($operation->id, $nextOperation));
                    }
                );
            }
        };
    }

    /**
     * Calculate next execution delay.
     */
    private function calculateExecutionDelay(NextScheduledOperation $nextScheduledOperation): int
    {
        /**
         * @noinspection PhpUnhandledExceptionInspection
         *
         * @var \DateTimeImmutable $currentDate
         */
        $currentDate = datetimeInstantiator('NOW');

        $executionDelay = $nextScheduledOperation->time->getTimestamp() - $currentDate->getTimestamp();

        return (int) bcmul((string) $executionDelay, '1000');
    }
}
