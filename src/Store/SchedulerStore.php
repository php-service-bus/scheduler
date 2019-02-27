<?php

/**
 * Scheduler implementation.
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Scheduler\Store;

use Amp\Promise;
use ServiceBus\Scheduler\Data\ScheduledOperation;
use ServiceBus\Scheduler\ScheduledOperationId;

/**
 *
 */
interface SchedulerStore
{
    /**
     * Extract operation (load and delete).
     *
     * @param ScheduledOperationId $id
     * @param callable             $postExtract
     *
     * @throws \ServiceBus\Scheduler\Store\Exceptions\ScheduledOperationNotFound
     * @throws \ServiceBus\Storage\Common\Exceptions\ConnectionFailed
     * @throws \ServiceBus\Storage\Common\Exceptions\StorageInteractingFailed
     * @throws \ServiceBus\Storage\Common\Exceptions\InvalidConfigurationOptions
     *
     * @return Promise
     */
    public function extract(ScheduledOperationId $id, callable $postExtract): Promise;

    /**
     * Remove operation.
     *
     * @param ScheduledOperationId $id
     * @param callable             $postRemove
     *
     * @throws \ServiceBus\Storage\Common\Exceptions\ConnectionFailed
     * @throws \ServiceBus\Storage\Common\Exceptions\StorageInteractingFailed
     * @throws \ServiceBus\Storage\Common\Exceptions\InvalidConfigurationOptions
     *
     * @return Promise It doesn't return any result
     */
    public function remove(ScheduledOperationId $id, callable $postRemove): Promise;

    /**
     * Save a new operation.
     *
     * @param ScheduledOperation $operation
     * @param callable           $postAdd
     *
     * @throws \ServiceBus\Storage\Common\Exceptions\ConnectionFailed
     * @throws \ServiceBus\Storage\Common\Exceptions\StorageInteractingFailed
     * @throws \ServiceBus\Storage\Common\Exceptions\InvalidConfigurationOptions
     *
     * @return Promise It doesn't return any result
     */
    public function add(ScheduledOperation $operation, callable $postAdd): Promise;
}
