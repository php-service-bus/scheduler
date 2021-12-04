<?php

declare(strict_types=1);

namespace ServiceBus\Scheduler\Tests;

use ServiceBus\Common\Context\IncomingMessageMetadata;
use function ServiceBus\Common\uuid;

/**
 *
 */
final class TestIncomingMetadata implements IncomingMessageMetadata
{
    public static function create(string $messageId, array $variables): self
    {
        return new self();
    }

    public function messageId(): string
    {
        return uuid();
    }

    public function traceId(): string
    {
        return uuid();
    }

    public function has(string $key): bool
    {
        return false;
    }

    public function get(string $key, float|bool|int|string|null $default = null): string|int|float|bool|null
    {
        return null;
    }

    public function with(string $key, float|bool|int|string|null $value): IncomingMessageMetadata
    {
        return new self();
    }

    public function variables(): array
    {
        return [];
    }
}
