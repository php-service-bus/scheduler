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
     *
     * @var ScheduledOperationId
     */
    public $id;

    /**
     * Command namespace.
     *
     * @psalm-var class-string
     *
     * @var string
     */
    public $commandNamespace;

    /**
     * Execution date.
     *
     * @var \DateTimeImmutable
     */
    public $executionDate;

    /**
     * Next operation data.
     *
     * @var NextScheduledOperation|null
     */
    public $nextOperation = null;

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
