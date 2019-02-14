<?php

/**
 * Scheduler implementation
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Scheduler\Contract;

use ServiceBus\Common\Messages\Command;
use ServiceBus\Scheduler\ScheduledOperationId;

/**
 * Fulfill the task of the scheduler
 *
 * @internal
 * @see SchedulerOperationEmitted
 *
 * @property-read ScheduledOperationId $id
 */
final class EmitSchedulerOperation implements Command
{
    /**
     * Scheduled operation identifier
     *
     * @var ScheduledOperationId
     */
    public $id;

    /**
     * @param ScheduledOperationId $id
     *
     * @return self
     */
    public static function create(ScheduledOperationId $id): self
    {
        return new self($id);
    }

    /**
     * @param ScheduledOperationId $id
     */
    private function __construct(ScheduledOperationId $id)
    {
        $this->id = $id;
    }
}
