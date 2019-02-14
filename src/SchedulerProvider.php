<?php

/**
 * Common scheduler implementation interfaces
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Scheduler\Common;

use function Amp\call;
use Amp\Promise;
use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Common\Messages\Command;
use ServiceBus\Scheduler\Common\Contract\OperationScheduled;
use ServiceBus\Scheduler\Common\Contract\SchedulerOperationCanceled;
use ServiceBus\Scheduler\Common\Exceptions\DuplicateScheduledOperation;
use ServiceBus\Scheduler\Common\Exceptions\ErrorCancelingScheduledOperation;
use ServiceBus\Scheduler\Common\Exceptions\OperationSchedulingError;
use ServiceBus\Scheduler\Common\Store\SchedulerStore;
use ServiceBus\Storage\Common\Exceptions\UniqueConstraintViolationCheckFailed;

/**
 * Scheduler provider
 */
final class SchedulerProvider
{
    /**
     * @var SchedulerStore
     */
    private $store;

    /**
     * @param SchedulerStore $store
     */
    public function __construct(SchedulerStore $store)
    {
        $this->store = $store;
    }

    /**
     * Schedule command execution
     *
     * @noinspection PhpDocRedundantThrowsInspection
     *
     * @param ScheduledOperationId $id
     * @param Command              $command
     * @param \DateTimeImmutable   $executionDate
     * @param ServiceBusContext    $context
     *
     * @return Promise Doesn't return result
     *
     * @throws \ServiceBus\Scheduler\Common\Exceptions\InvalidScheduledOperationExecutionDate
     * @throws \ServiceBus\Scheduler\Common\Exceptions\DuplicateScheduledOperation
     * @throws \ServiceBus\Scheduler\Common\Exceptions\OperationSchedulingError
     */
    public function schedule(
        ScheduledOperationId $id,
        Command $command,
        \DateTimeImmutable $executionDate,
        ServiceBusContext $context
    ): Promise
    {
        /** @psalm-suppress InvalidArgument */
        return call(
            function(ScheduledOperation $operation) use ($context): \Generator
            {
                try
                {
                    yield $this->store->add($operation, self::createPostAdd($context));

                    $context->logContextMessage('Operation "{messageClass}" scheduled', [
                            'messageClass' => \get_class($operation->command)
                        ]
                    );
                }
                catch(UniqueConstraintViolationCheckFailed $exception)
                {
                    throw new DuplicateScheduledOperation(
                        \sprintf('Job with ID "%s" already exists', $operation->id),
                        (int) $exception->getCode(),
                        $exception
                    );
                }
                catch(\Throwable $throwable)
                {
                    throw new OperationSchedulingError($throwable->getMessage(), (int) $throwable->getCode(), $throwable);
                }
            },
            ScheduledOperation::new($id, $command, $executionDate)
        );
    }

    /**
     * Cancel scheduled job
     *
     * @noinspection PhpDocRedundantThrowsInspection
     *
     * @param ScheduledOperationId $id
     * @param ServiceBusContext    $context
     * @param string|null          $reason
     *
     * @return Promise Doesn't return result
     *
     * @throws \ServiceBus\Scheduler\Common\Exceptions\ErrorCancelingScheduledOperation
     */
    public function cancel(ScheduledOperationId $id, ServiceBusContext $context, ?string $reason = null): Promise
    {
        /** @psalm-suppress InvalidArgument */
        return call(
            function(ScheduledOperationId $id, ServiceBusContext $context, ?string $reason): \Generator
            {
                try
                {
                    yield $this->store->remove($id, self::createPostCancel($context, $id, $reason));
                }
                catch(\Throwable $throwable)
                {
                    throw new ErrorCancelingScheduledOperation(
                        $throwable->getMessage(),
                        (int) $throwable->getCode(),
                        $throwable
                    );
                }
            },
            $id, $context, $reason
        );
    }

    /**
     * @psalm-return callable(NextScheduledOperation|null):Generator
     *
     * @param ServiceBusContext    $context
     * @param ScheduledOperationId $id
     * @param string|null          $reason
     *
     * @return callable
     */
    private static function createPostCancel(ServiceBusContext $context, ScheduledOperationId $id, ?string $reason): callable
    {
        return static function(?NextScheduledOperation $nextOperation) use ($id, $reason, $context): \Generator
        {
            yield $context->delivery(
                SchedulerOperationCanceled::create($id, $reason, $nextOperation)
            );
        };
    }

    /**
     * @psalm-return callable(ScheduledOperation, NextScheduledOperation|null):Generator
     *
     * @param ServiceBusContext $context
     *
     * @return callable
     */
    private static function createPostAdd(ServiceBusContext $context): callable
    {
        return static function(ScheduledOperation $operation, ?NextScheduledOperation $nextOperation) use ($context): \Generator
        {
            yield $context->delivery(
                OperationScheduled::create(
                    $operation->id,
                    $operation->command,
                    $operation->date,
                    $nextOperation
                )
            );
        };
    }
}
