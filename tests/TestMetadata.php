<?php

declare(strict_types = 1);

namespace ServiceBus\Scheduler\Tests;

use ServiceBus\Common\Context\Metadata;
use function ServiceBus\Common\uuid;

/**
 *
 */
final class TestMetadata implements Metadata
{
    /** @noinspection PhpUnnecessaryStaticReferenceInspection */
    public static function create(string $messageId, string $traceId, array $variables): static
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
}
