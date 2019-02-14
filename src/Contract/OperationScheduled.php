<?php

/**
 * Common scheduler implementation interfaces
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Scheduler\Common\Contract;

use ServiceBus\Common\Messages\Command;
use ServiceBus\Common\Messages\Event;
use ServiceBus\Scheduler\Common\NextScheduledOperation;
use ServiceBus\Scheduler\Common\ScheduledOperationId;

/**
 * Operation successful scheduled
 *
 * @property-read ScheduledOperationId        $id
 * @property-read string                      $commandNamespace
 * @property-read \DateTimeImmutable          $executionDate
 * @property-read NextScheduledOperation|null $nextOperation
 */
final class OperationScheduled implements Event
{
    /**
     * Operation identifier
     *
     * @var ScheduledOperationId
     */
    public $id;

    /**
     * Command namespace
     *
     * @var string
     */
    public $commandNamespace;

    /**
     * Execution date
     *
     * @var \DateTimeImmutable
     */
    public $executionDate;

    /**
     * Next operation data
     *
     * @var NextScheduledOperation|null
     */
    public $nextOperation;

    /**
     * @param ScheduledOperationId        $id
     * @param Command                     $command ,
     * @param \DateTimeImmutable          $executionDate
     * @param NextScheduledOperation|null $nextOperation
     *
     * @return self
     */
    public static function create(
        ScheduledOperationId $id,
        Command $command,
        \DateTimeImmutable $executionDate,
        ?NextScheduledOperation $nextOperation
    ): self
    {
        return new self($id, \get_class($command), $executionDate, $nextOperation);
    }

    /**
     * Has next operation data
     *
     * @return bool
     */
    public function hasNextOperation(): bool
    {
        return null !== $this->nextOperation;
    }

    /**
     * @param ScheduledOperationId        $id
     * @param string                      $commandNamespace
     * @param \DateTimeImmutable          $executionDate
     * @param NextScheduledOperation|null $nextOperation
     */
    private function __construct(
        ScheduledOperationId $id,
        string $commandNamespace,
        \DateTimeImmutable $executionDate,
        ?NextScheduledOperation $nextOperation
    )
    {
        $this->id               = $id;
        $this->commandNamespace = $commandNamespace;
        $this->executionDate    = $executionDate;
        $this->nextOperation    = $nextOperation;
    }
}
