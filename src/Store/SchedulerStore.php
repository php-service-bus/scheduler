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
use ServiceBus\Scheduler\Data\NextScheduledOperation;
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
     * @psalm-param callable(?ScheduledOperation, ?\ServiceBus\Scheduler\Data\NextScheduledOperation): void $postExtract
     *
     * @return Promise<void>
     *
     * @throws \ServiceBus\Scheduler\Store\Exceptions\ScheduledOperationNotFound
     * @throws \ServiceBus\Storage\Common\Exceptions\ConnectionFailed
     * @throws \ServiceBus\Storage\Common\Exceptions\StorageInteractingFailed
     * @throws \ServiceBus\Storage\Common\Exceptions\InvalidConfigurationOptions
     */
    public function extract(ScheduledOperationId $id, callable $postExtract): Promise;

    /**
     * Remove operation.
     *
     * @psalm-param callable(?\ServiceBus\Scheduler\Data\NextScheduledOperation):\Generator $postRemove
     *
     * @return Promise<void>
     *
     * @throws \ServiceBus\Storage\Common\Exceptions\ConnectionFailed
     * @throws \ServiceBus\Storage\Common\Exceptions\StorageInteractingFailed
     * @throws \ServiceBus\Storage\Common\Exceptions\InvalidConfigurationOptions
     */
    public function remove(ScheduledOperationId $id, callable $postRemove): Promise;

    /**
     * Save a new operation.
     *
     * @psalm-param callable(ScheduledOperation, ?\ServiceBus\Scheduler\Data\NextScheduledOperation):\Generator $postAdd
     *
     * @return Promise<void>
     *
     * @throws \ServiceBus\Storage\Common\Exceptions\ConnectionFailed
     * @throws \ServiceBus\Storage\Common\Exceptions\StorageInteractingFailed
     * @throws \ServiceBus\Storage\Common\Exceptions\InvalidConfigurationOptions
     */
    public function add(ScheduledOperation $operation, callable $postAdd): Promise;
}
