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
 * Scheduler operation canceled.
 *
 * @internal
 *
 * @psalm-readonly
 */
final class SchedulerOperationCanceled
{
    /**
     * Operation identifier.
     *
     * @var ScheduledOperationId
     */
    public $id;

    /**
     * Reason.
     *
     * @var string|null
     */
    public $reason = null;

    /**
     * Next operation data.
     *
     * @var NextScheduledOperation|null
     */
    public $nextOperation = null;

    /**
     * @param ScheduledOperationId        $id
     * @param string|null                 $reason
     * @param NextScheduledOperation|null $nextOperation
     */
    public function __construct(ScheduledOperationId $id, ?string $reason, ?NextScheduledOperation $nextOperation = null)
    {
        $this->id            = $id;
        $this->reason        = $reason;
        $this->nextOperation = $nextOperation;
    }
}
