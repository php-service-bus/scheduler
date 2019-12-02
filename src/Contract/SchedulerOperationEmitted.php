<?php

/**
 * Scheduler implementation.
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Scheduler\Contract;

use ServiceBus\Scheduler\Data\NextScheduledOperation;
use ServiceBus\Scheduler\ScheduledOperationId;

/**
 * Scheduler operation emitted.
 *
 * @see EmitSchedulerOperation
 *
 * @internal
 *
 * @psalm-readonly
 */
final class SchedulerOperationEmitted
{
    /**
     * Scheduled operation identifier.
     */
    public ScheduledOperationId $id;

    /**
     * Next operation data.
     */
    public ?NextScheduledOperation $nextOperation = null;

    public function __construct(ScheduledOperationId $id, ?NextScheduledOperation $nextOperation = null)
    {
        $this->id            = $id;
        $this->nextOperation = $nextOperation;
    }
}
