<?php

/**
 * Scheduler implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=0);

namespace ServiceBus\Scheduler;

use ServiceBus\Scheduler\Exceptions\EmptyScheduledOperationIdentifierNotAllowed;
use function ServiceBus\Common\uuid;

/**
 * @api
 * @psalm-immutable
 */
final class ScheduledOperationId
{
    /**
     * @psalm-var non-empty-string
     *
     * @var string
     */
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

    /**
     * @psalm-return non-empty-string
     */
    public function toString(): string
    {
        return $this->value;
    }

    /**
     * @psalm-param non-empty-string $value
     */
    private function __construct(string $value)
    {
        $this->value = $value;
    }
}
