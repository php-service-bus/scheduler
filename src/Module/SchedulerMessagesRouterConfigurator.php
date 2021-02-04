<?php

/**
 * Scheduler implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Scheduler\Module;

use ServiceBus\MessagesRouter\Exceptions\MessageRouterConfigurationFailed;
use ServiceBus\MessagesRouter\Router;
use ServiceBus\MessagesRouter\RouterConfigurator;
use ServiceBus\Scheduler\Contract\EmitSchedulerOperation;
use ServiceBus\Scheduler\Contract\OperationScheduled;
use ServiceBus\Scheduler\Contract\SchedulerOperationCanceled;
use ServiceBus\Scheduler\Contract\SchedulerOperationEmitted;
use ServiceBus\Scheduler\Emitter\SchedulerEmitter;
use ServiceBus\Scheduler\Processor\SchedulerMessagesProcessor;

/**
 *
 */
final class SchedulerMessagesRouterConfigurator implements RouterConfigurator
{
    /**
     * @var SchedulerEmitter
     */
    private $emitter;

    public function __construct(SchedulerEmitter $emitter)
    {
        $this->emitter = $emitter;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(Router $router): void
    {
        try
        {
            $processor = new SchedulerMessagesProcessor($this->emitter);

            $listenEvents = [
                SchedulerOperationEmitted::class,
                SchedulerOperationCanceled::class,
                OperationScheduled::class,
            ];

            foreach ($listenEvents as $event)
            {
                $router->registerListener($event, $processor);
            }

            $router->registerHandler(EmitSchedulerOperation::class, $processor);
        }
        catch (\Throwable $throwable)
        {
            throw new MessageRouterConfigurationFailed($throwable->getMessage(), (int) $throwable->getCode(), $throwable);
        }
    }
}
