<?php

/**
 * Scheduler implementation.
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Scheduler\Delivery;

use ServiceBus\Common\Endpoint\DeliveryOptions;

/**
 * @internal
 */
final class SchedulerDeliveryOptions implements DeliveryOptions
{
    /**
     * @psalm-var array<string, string|int|float>
     *
     * @var array
     */
    private $headers = [];

    /**
     * @var int|string|null
     */
    private $traceId;

    /**
     * @param int|string|null $traceId
     */
    public static function scheduledMessage($traceId, int $delay): self
    {
        $self = new self();

        $self->traceId            = $traceId;
        $self->headers['x-delay'] = $delay;

        return $self;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * {@inheritdoc}
     */
    public function withTraceId($traceId): void
    {
        $this->traceId = $traceId;
    }

    /**
     * {@inheritdoc}
     */
    public function withHeader(string $key, $value): void
    {
        /** @psalm-suppress MixedTypeCoercion */
        $this->headers[$key] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function traceId()
    {
        return $this->traceId;
    }

    /**
     * {@inheritdoc}
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * {@inheritdoc}
     */
    public function isPersistent(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHighestPriority(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function expirationAfter(): ?int
    {
        return null;
    }

    private function __construct()
    {
    }
}
