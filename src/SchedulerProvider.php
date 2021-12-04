<?php

/**
 * Scheduler implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=0);

namespace ServiceBus\Scheduler;

use Amp\Promise;
use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Scheduler\Contract\OperationScheduled;
use ServiceBus\Scheduler\Contract\SchedulerOperationCanceled;
use ServiceBus\Scheduler\Data\NextScheduledOperation;
use ServiceBus\Scheduler\Data\ScheduledOperation;
use ServiceBus\Scheduler\Exceptions\DuplicateScheduledOperation;
use ServiceBus\Scheduler\Exceptions\ErrorCancelingScheduledOperation;
use ServiceBus\Scheduler\Exceptions\OperationSchedulingError;
use ServiceBus\Scheduler\Store\SchedulerStore;
use ServiceBus\Storage\Common\Exceptions\UniqueConstraintViolationCheckFailed;
use function Amp\call;

/**
 * Scheduler provider.
 *
 * @api
 */
final class SchedulerProvider
{
    /**
     * @var SchedulerStore
     */
    private $store;

    public function __construct(SchedulerStore $store)
    {
        $this->store = $store;
    }

    /**
     * Schedule command execution.
     *
     * @throws \ServiceBus\Scheduler\Exceptions\InvalidScheduledOperationExecutionDate
     * @throws \ServiceBus\Scheduler\Exceptions\DuplicateScheduledOperation
     * @throws \ServiceBus\Scheduler\Exceptions\OperationSchedulingError
     */
    public function schedule(
        ScheduledOperationId $id,
        object $command,
        \DateTimeImmutable $executionDate,
        ServiceBusContext $context
    ): Promise {
        $operation = ScheduledOperation::new($id, $command, $executionDate);

        return call(
            function () use ($operation, $context): \Generator
            {
                try
                {
                    yield $this->store->add($operation, self::createPostAdd($context));

                    $context->logger()->debug(
                        'Operation "{messageClass}" successfully scheduled for {executionDate}',
                        [
                            'messageClass'  => \get_class($operation->command),
                            'executionDate' => $operation->date->format('Y-m-d H:i:s'),
                        ]
                    );
                }
                catch (UniqueConstraintViolationCheckFailed $exception)
                {
                    throw new DuplicateScheduledOperation(
                        \sprintf('Job with ID "%s" already exists', $operation->id->toString()),
                        (int) $exception->getCode(),
                        $exception
                    );
                }
                catch (\Throwable $throwable)
                {
                    throw new OperationSchedulingError($throwable->getMessage(), (int) $throwable->getCode(), $throwable);
                }
            }
        );
    }

    /**
     * Cancel scheduled job.
     *
     * @throws \ServiceBus\Scheduler\Exceptions\ErrorCancelingScheduledOperation
     */
    public function cancel(ScheduledOperationId $id, ServiceBusContext $context, ?string $reason = null): Promise
    {
        return call(
            function () use ($id, $context, $reason): \Generator
            {
                try
                {
                    yield $this->store->remove($id, self::createPostCancel($context, $id, $reason));
                }
                catch (\Throwable $throwable)
                {
                    throw new ErrorCancelingScheduledOperation(
                        $throwable->getMessage(),
                        (int) $throwable->getCode(),
                        $throwable
                    );
                }
            }
        );
    }

    /**
     * @psalm-return callable(?NextScheduledOperation):\Generator
     */
    private static function createPostCancel(ServiceBusContext $context, ScheduledOperationId $id, ?string $reason): callable
    {
        return static function (?NextScheduledOperation $nextOperation) use ($id, $reason, $context): \Generator
        {
            yield $context->delivery(
                new SchedulerOperationCanceled($id, $reason, $nextOperation)
            );
        };
    }

    /**
     * @psalm-return callable(ScheduledOperation, ?NextScheduledOperation):\Generator
     */
    private static function createPostAdd(ServiceBusContext $context): callable
    {
        return static function (ScheduledOperation $operation, ?NextScheduledOperation $nextOperation) use ($context): \Generator
        {
            /** @psalm-var class-string $commandClass */
            $commandClass = \get_class($operation->command);

            yield $context->delivery(
                new OperationScheduled(
                    $operation->id,
                    $commandClass,
                    $operation->date,
                    $nextOperation
                )
            );
        };
    }
}
