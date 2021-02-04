<?php

/**
 * Scheduler implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Scheduler\Tests;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use ServiceBus\Common\Context\ContextLogger;
use ServiceBus\Common\Context\Metadata;
use ServiceBus\Common\Context\ValidationViolations;
use Amp\Promise;
use Amp\Success;
use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Common\Endpoint\DeliveryOptions;

/**
 * @property-read object[] $messages
 */
final class Context implements ServiceBusContext
{
    /**
     * @psalm-var array<array-key, object>
     *
     * @var object[]
     */
    public $messages = [];

    /**
     * @var TestHandler
     */
    public $logHandler;

    /**
     * {@inheritdoc}
     */
    public function violations(): ?ValidationViolations
    {
        return null;
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
    public function delivery(
        object $message,
        ?DeliveryOptions $deliveryOptions = null,
        ?Metadata $withMetadata = null
    ): Promise {
        $this->messages[] = $message;

        return new Success();
    }

    /**
     * {@inheritdoc}
     */
    public function return(int $secondsDelay = 3, ?Metadata $withMetadata = null): Promise
    {
        return new Success();
    }

    public function logger(): ContextLogger
    {
        return new TestContextLogger(new Logger('test', [$this->logHandler]));
    }

    public function metadata(): Metadata
    {
        return new TestMetadata();
    }

    public function __construct()
    {
        $this->logHandler = new TestHandler();
        $this->logHandler->pushProcessor(new PsrLogMessageProcessor());
    }
}
