<?php

/**
 * Scheduler implementation
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Scheduler\Data;

use function ServiceBus\Common\datetimeInstantiator;
use ServiceBus\Scheduler\ScheduledOperationId;

/**
 * Scheduled job data (for next job)
 *
 * @internal
 *
 * @property-read ScheduledOperationId $id
 * @property-read \DateTimeImmutable   $time
 */
final class NextScheduledOperation
{
    /**
     * Job identifier
     *
     * @var ScheduledOperationId
     */
    public $id;

    /**
     * Next operation date
     *
     * @var \DateTimeImmutable
     */
    public $time;

    /**
     * @psalm-param array<string, string> $row
     *
     * @param array $row
     *
     * @return self
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

    /**
     * @param ScheduledOperationId $id
     * @param \DateTimeImmutable   $time
     *
     * @return self
     */
    public static function create(ScheduledOperationId $id, \DateTimeImmutable $time): self
    {
        return new self($id, $time);
    }

    /**
     * @param ScheduledOperationId $id
     * @param \DateTimeImmutable   $time
     */
    private function __construct(ScheduledOperationId $id, \DateTimeImmutable $time)
    {
        $this->id   = $id;
        $this->time = $time;
    }
}
