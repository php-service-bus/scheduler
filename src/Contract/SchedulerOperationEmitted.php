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

use ServiceBus\Common\Messages\Event;
use ServiceBus\Scheduler\Data\NextScheduledOperation;
use ServiceBus\Scheduler\ScheduledOperationId;

/**
 * Scheduler operation emitted
 *
 * @internal
 * @see EmitSchedulerOperation
 *
 * @property-read ScheduledOperationId        $id
 * @property-read NextScheduledOperation|null $nextOperation
 */
final class SchedulerOperationEmitted implements Event
{
    /**
     * Scheduled operation identifier
     *
     * @var ScheduledOperationId
     */
    public $id;

    /**
     * Next operation data
     *
     * @var NextScheduledOperation|null
     */
    public $nextOperation;

    /**
     * @param ScheduledOperationId        $id
     * @param NextScheduledOperation|null $nextScheduledOperation
     *
     * @return self
     */
    public static function create(ScheduledOperationId $id, ?NextScheduledOperation $nextScheduledOperation = null): self
    {
        return new self($id, $nextScheduledOperation);
    }

    /**
     * @param ScheduledOperationId        $id
     * @param NextScheduledOperation|null $nextOperation
     */
    public function __construct(ScheduledOperationId $id, ?NextScheduledOperation $nextOperation)
    {
        $this->id            = $id;
        $this->nextOperation = $nextOperation;
    }
}
