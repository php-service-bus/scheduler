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
 * Operation successful scheduled.
 *
 * @internal
 *
 * @psalm-readonly
 */
final class OperationScheduled
{
    /**
     * Operation identifier.
     */
    public ScheduledOperationId $id;

    /**
     * Command namespace.
     *
     * @psalm-var class-string
     */
    public string $commandNamespace;

    /**
     * Execution date.
     */
    public \DateTimeImmutable $executionDate;

    /**
     * Next operation data.
     */
    public ?NextScheduledOperation $nextOperation = null;

    /**
     * @psalm-param class-string $commandNamespace
     *
     * @param ScheduledOperationId        $id
     * @param string                      $commandNamespace
     * @param \DateTimeImmutable          $executionDate
     * @param NextScheduledOperation|null $nextOperation
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

    /**
     * Has next operation data.
     */
    public function hasNextOperation(): bool
    {
        return null !== $this->nextOperation;
    }
}
