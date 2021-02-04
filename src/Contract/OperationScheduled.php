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
 * Operation successful scheduled.
 *
 * @psalm-immutable
 */
final class OperationScheduled
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
     * Command namespace.
     *
     * @psalm-readonly
     * @psalm-var class-string
     *
     * @var string
     */
    public $commandNamespace;

    /**
     * Execution date.
     *
     * @psalm-readonly
     *
     * @var \DateTimeImmutable
     */
    public $executionDate;

    /**
     * Next operation data.
     *
     * @psalm-readonly
     *
     * @var NextScheduledOperation|null
     */
    public $nextOperation;

    /**
     * @psalm-param class-string $commandNamespace
     */
    public function __construct(
        ScheduledOperationId $id,
        string $commandNamespace,
        \DateTimeImmutable $executionDate,
        ?NextScheduledOperation $nextOperation
    ) {
        $this->id               = $id;
        $this->commandNamespace = $commandNamespace;
        $this->executionDate    = $executionDate;
        $this->nextOperation    = $nextOperation;
    }

    public function hasNextOperation(): bool
    {
        return null !== $this->nextOperation;
    }
}
