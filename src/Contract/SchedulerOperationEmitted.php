<?php

/**
 * Scheduler implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Scheduler\Contract;

use ServiceBus\Scheduler\Data\NextScheduledOperation;
use ServiceBus\Scheduler\ScheduledOperationId;

/**
 * Scheduler operation emitted.
 *
 * @see EmitSchedulerOperation
 *
 * @psalm-immutable
 */
final class SchedulerOperationEmitted
{
    /**
     * Scheduled operation identifier.
     *
     * @psalm-readonly
     *
     * @var ScheduledOperationId
     */
    public $id;

    /**
     * Next operation data
     *
     * @psalm-readonly
     *
     * @var NextScheduledOperation|null
     */
    public $nextOperation;

    public function __construct(ScheduledOperationId $id, ?NextScheduledOperation $nextOperation = null)
    {
        $this->id            = $id;
        $this->nextOperation = $nextOperation;
    }
}
