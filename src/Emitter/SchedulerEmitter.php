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
use ServiceBus\Scheduler\Data\NextScheduledOperation;
use ServiceBus\Scheduler\ScheduledOperationId;

/**
 *
 */
interface SchedulerEmitter
{
    /**
     * Emit operation (send scheduled command to destination queue)
     * Deletes the task from the database (@see SchedulerStore::extract()) after the event is sent.
     *
     * @psalm-return Promise<void>
     *
     * @see SchedulerOperationEmitted event
     *
     * @throws \ServiceBus\Scheduler\Exceptions\EmitFailed
     */
    public function emit(ScheduledOperationId $id, ServiceBusContext $context): Promise;

    /**
     * Emit next operation.
     *
     * @psalm-return Promise<void>
     *
     * @see EmitSchedulerOperation command
     *
     * @throws \ServiceBus\Scheduler\Exceptions\EmitFailed
     */
    public function emitNextOperation(?NextScheduledOperation $nextOperation, ServiceBusContext $context): Promise;
}
