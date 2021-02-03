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
     *
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     * @see https://github.com/vimeo/psalm/issues/5070
     */
    public static function create(): static
    {
        return new static();
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
        $this->headers[$key] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function traceId(): mixed
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
