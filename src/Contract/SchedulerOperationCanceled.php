<?php

/**
 * Scheduler implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=0);

namespace ServiceBus\Scheduler\Contract;

use ServiceBus\Scheduler\Data\NextScheduledOperation;
use ServiceBus\Scheduler\ScheduledOperationId;

/**
 * Scheduler operation canceled.
 *
 * @psalm-immutable
 */
final class SchedulerOperationCanceled
{
    /**
     * Operation identifier.
     *
     * @psalm-readonly
     *
     * @var ScheduledOperationId
     */
    public $id;

    /**
     * Reason.
     *
     * @psalm-readonly
     *
     * @var string|null
     */
    public $reason;

    /**
     * Next operation data.
     *
     * @psalm-readonly
     *
     * @var NextScheduledOperation|null
     */
    public $nextOperation;

    public function __construct(ScheduledOperationId $id, ?string $reason, ?NextScheduledOperation $nextOperation = null)
    {
        $this->id            = $id;
        $this->reason        = $reason;
        $this->nextOperation = $nextOperation;
    }
}
