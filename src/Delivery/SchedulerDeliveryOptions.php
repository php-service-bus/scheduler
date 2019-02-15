<?php

/**
 * Scheduler implementation
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
     * @var array<string, string|int|float>
     */
    private $headers = [];

    /**
     * @var string|int|null
     */
    private $traceId;

    /**
     * @param string|int|null $traceId
     * @param int             $delay
     *
     * @return self
     */
    public static function scheduledMessage($traceId, int $delay): self
    {
        $self = new self();

        $self->traceId            = $traceId;
        $self->headers['x-delay'] = $delay;

        return $self;
    }

    /**
     * @inheritDoc
     */
    public static function create(): DeliveryOptions
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function withTraceId($traceId): void
    {
        $this->traceId;
    }

    /**
     * @inheritDoc
     */
    public function withHeader(string $key, $value): void
    {
        /** @psalm-suppress MixedTypeCoercion */
        $this->headers[$key] = $value;
    }

    /**
     * @inheritDoc
     */
    public function traceId()
    {
        return $this->traceId;
    }

    /**
     * @inheritDoc
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * @inheritDoc
     */
    public function isPersistent(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function isHighestPriority(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function expirationAfter(): ?int
    {
        return null;
    }

    private function __construct()
    {

    }
}
