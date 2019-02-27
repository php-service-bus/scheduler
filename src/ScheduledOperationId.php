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
 */
final class ScheduledOperationId
{
    /**
     * @var string
     */
    private $value;

    /**
     * @return self
     */
    public static function new(): self
    {
        return new self(uuid());
    }

    /**
     * @param string $value
     *
     * @throws \ServiceBus\Scheduler\Exceptions\EmptyScheduledOperationIdentifierNotAllowed
     *
     * @return ScheduledOperationId
     */
    public static function restore(string $value): self
    {
        if ('' === $value)
        {
            throw new EmptyScheduledOperationIdentifierNotAllowed('Scheduled operation ID can\'t be empty');
        }

        return new self($value);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    private function __construct(string $value)
    {
        $this->value = $value;
    }
}
