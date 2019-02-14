<?php

/**
 * Common scheduler implementation
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Scheduler\Common\Contract;

use ServiceBus\Common\Messages\Event;
use ServiceBus\Scheduler\Common\NextScheduledOperation;
use ServiceBus\Scheduler\Common\ScheduledOperationId;

/**
 * Scheduler operation canceled
 *
 * @property-read ScheduledOperationId        $id
 * @property-read string|null                 $reason
 * @property-read NextScheduledOperation|null $nextOperation
 */
final class SchedulerOperationCanceled implements Event
{
    /**
     * Operation identifier
     *
     * @var ScheduledOperationId
     */
    public $id;

    /**
     * Reason
     *
     * @var string|null
     */
    public $reason;

    /**
     * Next operation data
     *
     * @var NextScheduledOperation|null
     */
    public $nextOperation;

    /**
     * @param ScheduledOperationId        $id
     * @param string|null                 $reason
     * @param NextScheduledOperation|null $nextScheduledOperation
     *
     * @return self
     */
    public static function create(
        ScheduledOperationId $id,
        ?string $reason,
        ?NextScheduledOperation $nextScheduledOperation = null
    ): self
    {
        return new self($id, $reason, $nextScheduledOperation);
    }

    /**
     * @param ScheduledOperationId        $id
     * @param string|null                 $reason
     * @param NextScheduledOperation|null $nextOperation
     */
    private function __construct(ScheduledOperationId $id, ?string $reason, ?NextScheduledOperation $nextOperation)
    {
        $this->id            = $id;
        $this->reason        = $reason;
        $this->nextOperation = $nextOperation;
    }
}
