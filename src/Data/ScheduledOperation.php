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

use ServiceBus\Scheduler\Exceptions\InvalidScheduledOperationExecutionDate;
use ServiceBus\Scheduler\Exceptions\UnserializeCommandFailed;
use ServiceBus\Scheduler\ScheduledOperationId;
use function ServiceBus\Common\datetimeInstantiator;
use function ServiceBus\Common\now;

/**
 * Scheduled job data.
 *
 * @psalm-immutable
 */
final class ScheduledOperation
{
    /**
     * Identifier.
     *
     * @psalm-readonly
     *
     * @var ScheduledOperationId
     */
    public $id;

    /**
     * Scheduled message.
     *
     * @psalm-readonly
     *
     * @var object
     */
    public $command;

    /**
     * Execution date.
     *
     * @psalm-readonly
     *
     * @var \DateTimeImmutable
     */
    public $date;

    /**
     * The message was sent to the transport.
     *
     * @psalm-readonly
     *
     * @var bool
     */
    public $isSent;

    /**
     * @throws \ServiceBus\Scheduler\Exceptions\InvalidScheduledOperationExecutionDate
     */
    public static function new(ScheduledOperationId $id, object $command, \DateTimeImmutable $dateTime): self
    {
        self::validateDatetime($dateTime);

        return new self($id, $command, $dateTime);
    }

    /**
     * @psalm-param array{processing_date:string, command:string, id:string, is_sent:bool} $data
     *
     * @throws \ServiceBus\Scheduler\Exceptions\EmptyScheduledOperationIdentifierNotAllowed
     * @throws \ServiceBus\Scheduler\Exceptions\UnserializeCommandFailed
     * @throws \ServiceBus\Common\Exceptions\DateTimeException
     */
    public static function restoreFromRow(array $data): self
    {
        /** @var \DateTimeImmutable $dateTime */
        $dateTime = datetimeInstantiator($data['processing_date']);

        $serializedCommand = \base64_decode($data['command']);

        if (\is_string($serializedCommand))
        {
            /** @var false|object $command */
            $command = \unserialize($serializedCommand, ['allowed_classes' => true]);

            if (\is_object($command))
            {
                return new self(
                    ScheduledOperationId::restore($data['id']),
                    $command,
                    $dateTime,
                    $data['is_sent']
                );
            }
        }

        throw new UnserializeCommandFailed('Command deserialization error');
    }

    private function __construct(
        ScheduledOperationId $id,
        object $command,
        \DateTimeImmutable $dateTime,
        bool $isSent = false
    ) {
        $this->id      = $id;
        $this->command = $command;
        $this->date    = $dateTime;
        $this->isSent  = $isSent;
    }

    /**
     * @throws \ServiceBus\Scheduler\Exceptions\InvalidScheduledOperationExecutionDate
     */
    private static function validateDatetime(\DateTimeImmutable $dateTime): void
    {
        try
        {
            if (now() >= $dateTime)
            {
                throw new \InvalidArgumentException(
                    'The date of the scheduled task should be greater than the current one'
                );
            }
        }
        catch (\Throwable $throwable)
        {
            throw new InvalidScheduledOperationExecutionDate(
                $throwable->getMessage(),
                (int) $throwable->getCode(),
                $throwable
            );
        }
    }
}
