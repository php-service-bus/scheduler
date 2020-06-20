<?php

/**
 * Scheduler implementation.
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Scheduler;

use function ServiceBus\Common\uuid;
use ServiceBus\Scheduler\Exceptions\EmptyScheduledOperationIdentifierNotAllowed;

/**
 * @api
 * @psalm-immutable
 */
final class ScheduledOperationId
{
    /** @var string  */
    private $value;

    public static function new(): self
    {
        return new self(uuid());
    }

    /**
     * @throws \ServiceBus\Scheduler\Exceptions\EmptyScheduledOperationIdentifierNotAllowed
     */
    public static function restore(string $value): self
    {
        if ($value === '')
        {
            throw new EmptyScheduledOperationIdentifierNotAllowed('Scheduled operation ID can\'t be empty');
        }

        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    private function __construct(string $value)
    {
        $this->value = $value;
    }
}
