<?php

/**
 * Common scheduler implementation
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Scheduler\Common;

use function ServiceBus\Common\datetimeInstantiator;

/**
 * Scheduled job data (for next job)
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
     * @param array<string, string> $row
     *
     * @return self
     *
     * @throws \ServiceBus\Scheduler\Common\Exceptions\EmptyScheduledOperationIdentifierNotAllowed
     * @throws \ServiceBus\Common\Exceptions\DateTime\CreateDateTimeFailed
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
