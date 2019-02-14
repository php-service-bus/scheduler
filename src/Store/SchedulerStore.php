<?php

/**
 * Common scheduler implementation interfaces
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Scheduler\Common\Store;

use Amp\Promise;
use ServiceBus\Scheduler\Common\ScheduledOperation;
use ServiceBus\Scheduler\Common\ScheduledOperationId;

/**
 *
 */
interface SchedulerStore
{
    /**
     * Extract operation (load and delete)
     *
     * @psalm-param callable(ScheduledOperation|null, ?NextScheduledOperation|null):\Generator $postExtract
     *
     * @param ScheduledOperationId $id
     * @param callable             $postExtract
     *
     * @return Promise
     *
     * @throws \ServiceBus\Scheduler\Common\Store\Exceptions\ScheduledOperationNotFound
     * @throws \ServiceBus\Storage\Common\Exceptions\ConnectionFailed
     * @throws \ServiceBus\Storage\Common\Exceptions\StorageInteractingFailed
     * @throws \ServiceBus\Storage\Common\Exceptions\InvalidConfigurationOptions
     */
    public function extract(ScheduledOperationId $id, callable $postExtract): Promise;

    /**
     * Remove operation
     *
     * @psalm-param callable(NextScheduledOperation|null):Generator $postRemove
     *
     * @param ScheduledOperationId $id
     * @param callable             $postRemove
     *
     * @return Promise It doesn't return any result
     *
     * @throws \ServiceBus\Storage\Common\Exceptions\ConnectionFailed
     * @throws \ServiceBus\Storage\Common\Exceptions\StorageInteractingFailed
     * @throws \ServiceBus\Storage\Common\Exceptions\InvalidConfigurationOptions
     */
    public function remove(ScheduledOperationId $id, callable $postRemove): Promise;

    /**
     * Save a new operation
     *
     * @psalm-param callable(ScheduledOperation, NextScheduledOperation|null):Generator $postAdd
     *
     * @param ScheduledOperation $operation
     * @param callable           $postAdd
     *
     * @return Promise It doesn't return any result
     *
     * @throws \ServiceBus\Storage\Common\Exceptions\ConnectionFailed
     * @throws \ServiceBus\Storage\Common\Exceptions\StorageInteractingFailed
     * @throws \ServiceBus\Storage\Common\Exceptions\InvalidConfigurationOptions
     */
    public function add(ScheduledOperation $operation, callable $postAdd): Promise;
}
