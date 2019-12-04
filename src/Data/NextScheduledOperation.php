<?php

/**
 * Scheduler implementation.
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
 * Scheduled job data (for next job).
 *
 * @internal
 *
 * @psalm-readonly
 */
final class NextScheduledOperation
{
    /**
     * Job identifier.
     *
     * @var ScheduledOperationId
     */
    public $id;

    /**
     * Next operation date.
     *
     * @var \DateTimeImmutable
     */
    public $time;

    /**
     * @psalm-param array<string, string> $row
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
