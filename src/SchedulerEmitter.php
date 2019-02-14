<?php

/**
 * Common scheduler implementation
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Scheduler\Common;

use Amp\Promise;
use ServiceBus\Common\Context\ServiceBusContext;

/**
 *
 */
interface SchedulerEmitter
{
    /**
     * Emit operation (send scheduled command to destination queue)
     * Deletes the task from the database (@see SchedulerStore::extract()) after the event is sent
     *
     * @see SchedulerOperationEmitted event
     *
     * @param ScheduledOperationId $id
     * @param ServiceBusContext    $context
     *
     * @return Promise
     *
     * @throws \ServiceBus\Scheduler\Common\Exceptions\EmitFailed
     */
    public function emit(ScheduledOperationId $id, ServiceBusContext $context): Promise;

    /**
     * Emit next operation
     *
     * @see EmitSchedulerOperation command
     *
     * @param NextScheduledOperation|null $nextOperation
     * @param ServiceBusContext           $context
     *
     * @return Promise
     *
     * @throws \ServiceBus\Scheduler\Common\Exceptions\EmitFailed
     */
    public function emitNextOperation(?NextScheduledOperation $nextOperation, ServiceBusContext $context): Promise;
}
