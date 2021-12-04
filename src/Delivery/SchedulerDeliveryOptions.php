<?php

/**
 * Scheduler implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=0);

namespace ServiceBus\Scheduler\Delivery;

use ServiceBus\Common\Endpoint\DeliveryOptions;

final class SchedulerDeliveryOptions implements DeliveryOptions
{
    /**
     * @psalm-var array<string, int|float|string|null>
     *
     * @var array
     */
    private $headers;

    /**
     * @psalm-param positive-int $delay
     */
    public static function scheduledMessage(int $delay): self
    {
        return new self(['x-delay' => $delay]);
    }

    public static function create(): self
    {
        return new self([]);
    }

    public function withHeader(string $key, int|float|string|null $value): self
    {
        $headers = $this->headers;
        $headers[$key] = $value;

        return new self($headers);
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function isPersistent(): bool
    {
        return true;
    }

    public function isHighestPriority(): bool
    {
        return true;
    }

    public function expirationAfter(): ?int
    {
        return null;
    }

    /**
     * @psalm-param array<string, int|float|string|null> $headers
     */
    private function __construct(array $headers)
    {
        $this->headers = $headers;
    }
}
