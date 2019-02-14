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
     * @param string|int|null                 $traceId
     * @param array<string, string|int|float> $headers
     *
     * @return self
     */
    public static function scheduledMessage($traceId, array $headers): self
    {
        $self = new self();

        $self->traceId = $traceId;
        $self->headers = $headers;

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

    private function __construct()
    {

    }
}
