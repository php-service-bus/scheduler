<?php

/**
 * Scheduler implementation.
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Scheduler;

use function Amp\call;
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

/**
 * Scheduler provider.
 *
 * @api
 */
final class SchedulerProvider
{
    private SchedulerStore $store;

    public function __construct(SchedulerStore $store)
    {
        $this->store = $store;
    }

    /**
     * Schedule command execution.
     *
     * @noinspection PhpDocRedundantThrowsInspection
     *
     * @throws \ServiceBus\Scheduler\Exceptions\InvalidScheduledOperationExecutionDate
     * @throws \ServiceBus\Scheduler\Exceptions\DuplicateScheduledOperation
     * @throws \ServiceBus\Scheduler\Exceptions\OperationSchedulingError
     *
     * @return Promise Doesn't return result
     */
    public function schedule(
        ScheduledOperationId $id,
        object $command,
        \DateTimeImmutable $executionDate,
        ServiceBusContext $context
    ): Promise {
        return call(
            function (ScheduledOperation $operation) use ($context): \Generator
            {
                try
                {
                    yield $this->store->add($operation, self::createPostAdd($context));

                    $context->logContextMessage(
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
            },
            ScheduledOperation::new($id, $command, $executionDate)
        );
    }

    /**
     * Cancel scheduled job.
     *
     * @noinspection PhpDocRedundantThrowsInspection
     *
     * @throws \ServiceBus\Scheduler\Exceptions\ErrorCancelingScheduledOperation
     *
     * @return Promise Doesn't return result
     */
    public function cancel(ScheduledOperationId $id, ServiceBusContext $context, ?string $reason = null): Promise
    {
        return call(
            function (ScheduledOperationId $id, ServiceBusContext $context, ?string $reason): \Generator
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
            },
            $id,
            $context,
            $reason
        );
    }

    private static function createPostCancel(ServiceBusContext $context, ScheduledOperationId $id, ?string $reason): callable
    {
        return static function (?NextScheduledOperation $nextOperation) use ($id, $reason, $context): \Generator
        {
            yield $context->delivery(
                new SchedulerOperationCanceled($id, $reason, $nextOperation)
            );
        };
    }

    private static function createPostAdd(ServiceBusContext $context): callable
    {
        return static function (ScheduledOperation $operation, ?NextScheduledOperation $nextOperation) use ($context): \Generator
        {
            /** @psalm-var class-string $commandClass */
            $commandClass = (string) \get_class($operation->command);

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
