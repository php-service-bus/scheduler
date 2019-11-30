<?php

/**
 * Scheduler implementation.
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Scheduler\Tests;

use function ServiceBus\Common\uuid;
use Amp\Promise;
use Amp\Success;
use Psr\Log\LogLevel;
use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Common\Endpoint\DeliveryOptions;

/**
 * @property-read object[] $messages
 */
final class Context implements ServiceBusContext
{
    /**
     * @psalm-var array<array-key, \ServiceBus\Common\Messages\Message>
     *
     * @var object[]
     */
    public array

 $messages = [];

    /**
     * {@inheritdoc}
     */
    public function isValid(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function violations(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function headers(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function delivery(object $message, ?DeliveryOptions $deliveryOptions = null): Promise
    {
        $this->messages[] = $message;

        return new Success();
    }

    /**
     * {@inheritdoc}
     */
    public function return($secondsDelay = 3): Promise
    {
        return new Success();
    }

    /**
     * {@inheritdoc}
     */
    public function logContextMessage(string $logMessage, array $extra = [], string $level = LogLevel::INFO): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function logContextThrowable(\Throwable $throwable, array $extra = [], string $level = LogLevel::ERROR): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function operationId(): string
    {
        return uuid();
    }

    /**
     * {@inheritdoc}
     */
    public function traceId(): string
    {
        return uuid();
    }
}
