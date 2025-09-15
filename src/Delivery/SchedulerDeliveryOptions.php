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

/**
 * @psalm-immutable
 */
final class SchedulerDeliveryOptions implements DeliveryOptions
{
    /**
     * @psalm-var array<string, int|float|string|null>
     *
     * @var array
     */
    private $headers;

    /**
     * This flag tells the server how to react if the message cannot be routed to a queue consumer immediately. If this
     * flag is set, the server will return an undeliverable message with a Return method. If this flag is false, the
     * server will queue the message, but with no guarantee that it will ever be consumed.
     *
     * @var bool
     */
    private $isImmediate;

    /**
     * @psalm-param positive-int $delay
     */
    public static function scheduledMessage(int $delay): self
    {
        return new self(['x-delay' => $delay]);
    }

    public function withIsHighestPriority(bool $isHighestPriority): self
    {
        return new self(
            headers: $this->headers,
            isImmediate: $isHighestPriority,
        );
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
        return $this->isImmediate;
    }

    public function expirationAfter(): ?int
    {
        return null;
    }

    /**
     * @psalm-param array<string, int|float|string|null> $headers
     */
    private function __construct(
        array $headers,
        bool $isImmediate = true,
    ) {
        $this->headers = $headers;
        $this->isImmediate = $isImmediate;
    }
}
