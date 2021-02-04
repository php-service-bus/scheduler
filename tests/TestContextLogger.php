<?php

declare(strict_types = 1);

namespace ServiceBus\Scheduler\Tests;

use Monolog\Logger;
use Psr\Log\LogLevel;
use ServiceBus\Common\Context\ContextLogger;
use function ServiceBus\Common\throwableMessage;

/**
 *
 */
final class TestContextLogger implements ContextLogger
{
    /**
     * @var Logger
     */
    private $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function contextMessage(string $logMessage, array $extra = [], string $level = LogLevel::INFO): void
    {
        $this->log($level, $logMessage, $extra);
    }

    public function contextThrowable(\Throwable $throwable, array $extra = [], string $level = LogLevel::ERROR): void
    {
        $this->log($level, throwableMessage($throwable), $extra);
    }

    public function emergency($message, array $context = []): void
    {
        $this->logger->emergency($message, $context);
    }

    public function alert($message, array $context = []): void
    {
        $this->logger->alert($message, $context);
    }

    public function critical($message, array $context = []): void
    {
        $this->logger->critical($message, $context);
    }

    public function error($message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    public function warning($message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    public function notice($message, array $context = []): void
    {
        $this->logger->notice($message, $context);
    }

    public function info($message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    public function debug($message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

    public function log($level, $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }
}
