<?php

/**
 * Scheduler implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=0);

namespace ServiceBus\Scheduler\Data;

use ServiceBus\Scheduler\ScheduledOperationId;
use function ServiceBus\Common\datetimeInstantiator;

/**
 * Scheduled job data (for next job).
 *
 * @psalm-immutable
 */
final class NextScheduledOperation
{
    /**
     * Job identifier.
     *
     * @psalm-readonly
     *
     * @var ScheduledOperationId
     */
    public $id;

    /**
     * Next operation date.
     *
     * @psalm-readonly
     *
     * @var \DateTimeImmutable
     */
    public $time;

    /**
     * @psalm-param array{processing_date:non-empty-string,id:non-empty-string} $row
     *
     * @throws \ServiceBus\Scheduler\Exceptions\EmptyScheduledOperationIdentifierNotAllowed
     * @throws \ServiceBus\Common\Exceptions\DateTimeException
     */
    public static function fromRow(array $row): self
    {
        /** @var \DateTimeImmutable $datetime */
        $datetime = datetimeInstantiator($row['processing_date']);

        return new self(
            ScheduledOperationId::restore($row['id']),
            $datetime
        );
    }

    public static function create(ScheduledOperationId $id, \DateTimeImmutable $time): self
    {
        return new self($id, $time);
    }

    private function __construct(ScheduledOperationId $id, \DateTimeImmutable $time)
    {
        $this->id   = $id;
        $this->time = $time;
    }
}
