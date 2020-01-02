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

use ServiceBus\Scheduler\ScheduledOperationId;

/**
 * Fulfill the task of the scheduler.
 *
 * @see SchedulerOperationEmitted
 *
 * @psalm-immutable
 */
final class EmitSchedulerOperation
{
    /**
     * Scheduled operation identifier.
     *
     * @var ScheduledOperationId
     */
    public $id;

    public function __construct(ScheduledOperationId $id)
    {
        $this->id = $id;
    }
}
