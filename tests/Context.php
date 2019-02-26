<?php

/**
 * Scheduler implementation
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Scheduler\Tests;

use Amp\Promise;
use Amp\Success;
use Psr\Log\LogLevel;
use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Common\Endpoint\DeliveryOptions;
use function ServiceBus\Common\uuid;

/**
 * @property-read object[] $messages
 */
final class Context implements ServiceBusContext
{
    /**
     * @psalm-var array<array-key, \ServiceBus\Common\Messages\Message>
     * @var object[]
     */
    public $messages = [];

    /**
     * @inheritDoc
     */
    public function isValid(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function violations(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function delivery(object $message, ?DeliveryOptions $deliveryOptions = null): Promise
    {
        $this->messages[] = $message;

        return new Success();
    }

    /**
     * @inheritDoc
     */
    public function logContextMessage(string $logMessage, array $extra = [], string $level = LogLevel::INFO): void
    {

    }

    /**
     * @inheritDoc
     */
    public function logContextThrowable(\Throwable $throwable, string $level = LogLevel::ERROR, array $extra = []): void
    {

    }

    /**
     * @inheritDoc
     */
    public function operationId(): string
    {
        return uuid();
    }

    /**
     * @inheritDoc
     */
    public function traceId(): string
    {
        return uuid();
    }

}
