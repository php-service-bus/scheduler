<?php

/**
 * Scheduler implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Scheduler\Delivery;

use ServiceBus\Common\Endpoint\DeliveryOptions;

/**
 *
 */
final class SchedulerDeliveryOptions implements DeliveryOptions
{
    /**
     * @psalm-var array<string, int|float|string|null>
     *
     * @var array
     */
    private $headers;

    public static function scheduledMessage(int $delay): self
    {
        return new self(['x-delay' => $delay]);
    }

    /**
     * @noinspection PhpUnnecessaryStaticReferenceInspection
     * @psalm-suppress LessSpecificReturnStatement
     * @psalm-suppress MoreSpecificReturnType
     */
    public static function create(): static
    {
        return new self([]);
    }

    public function withHeader(string $key, int|float|string|null $value): void
    {
        $this->headers[$key] = $value;
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
