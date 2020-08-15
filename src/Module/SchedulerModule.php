<?php

/**
 * Scheduler implementation.
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Scheduler\Module;

use ServiceBus\Common\Module\ServiceBusModule;
use ServiceBus\MessagesRouter\ChainRouterConfigurator;
use ServiceBus\MessagesRouter\Router;
use ServiceBus\Scheduler\Emitter\RabbitMQEmitter;
use ServiceBus\Scheduler\Emitter\SchedulerEmitter;
use ServiceBus\Scheduler\SchedulerProvider;
use ServiceBus\Scheduler\Store\SchedulerStore;
use ServiceBus\Scheduler\Store\SqlSchedulerStore;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 *
 */
final class SchedulerModule implements ServiceBusModule
{
    private const TYPE = 'rabbitmq';

    /** @var string */
    private $adapterType;

    /** @var string */
    private $storeImplementationServiceId;

    /** @var string */
    private $databaseAdapterServiceId;

    public static function rabbitMqWithSqlStorage(string $databaseAdapterServiceId): self
    {
        return new self(
            self::TYPE,
            SqlSchedulerStore::class,
            $databaseAdapterServiceId
        );
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerBuilder $containerBuilder): void
    {
        $this->registerSchedulerStore($containerBuilder);
        $this->registerSchedulerProvider($containerBuilder);
        $this->registerEmitter($containerBuilder);
        $this->registerSchedulerMessagesRouterConfigurator($containerBuilder);

        $routerConfiguratorDefinition = $this->getRouterConfiguratorDefinition($containerBuilder);

        $routerConfiguratorDefinition->addMethodCall(
            'addConfigurator',
            [new Reference(SchedulerMessagesRouterConfigurator::class)]
        );
    }

    /**
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    private function getRouterConfiguratorDefinition(ContainerBuilder $containerBuilder): Definition
    {
        if (false === $containerBuilder->hasDefinition(ChainRouterConfigurator::class))
        {
            $containerBuilder->addDefinitions(
                [
                    ChainRouterConfigurator::class => new Definition(ChainRouterConfigurator::class),
                ]
            );
        }

        $routerConfiguratorDefinition = $containerBuilder->getDefinition(ChainRouterConfigurator::class);

        if (false === $containerBuilder->hasDefinition(Router::class))
        {
            $containerBuilder->addDefinitions([Router::class => new Definition(Router::class)]);
        }

        /** @var Definition $routerConfiguratorDefinition */

        return $routerConfiguratorDefinition;
    }

    private function registerSchedulerMessagesRouterConfigurator(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            SchedulerMessagesRouterConfigurator::class => (new Definition(SchedulerMessagesRouterConfigurator::class))
                ->setArguments([new Reference(SchedulerEmitter::class)]),
        ]);
    }

    /**
     * @throws \LogicException
     */
    private function registerEmitter(ContainerBuilder $containerBuilder): void
    {
        if (self::TYPE === $this->adapterType)
        {
            $containerBuilder->addDefinitions([
                SchedulerEmitter::class => (new Definition(RabbitMQEmitter::class))
                    ->setArguments([new Reference(SchedulerStore::class)]),
            ]);

            return;
        }

        throw new \LogicException('Wrong adapter type');
    }

    private function registerSchedulerStore(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            SchedulerStore::class => (new Definition($this->storeImplementationServiceId))
                ->setArguments([new Reference($this->databaseAdapterServiceId)]),
        ]);
    }

    private function registerSchedulerProvider(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            SchedulerProvider::class => (new Definition(SchedulerProvider::class))
                ->setArguments([new Reference(SchedulerStore::class)]),
        ]);
    }

    private function __construct(
        string $adapterType,
        string $storeImplementationServiceId,
        string $databaseAdapterServiceId
    ) {
        $this->adapterType                  = $adapterType;
        $this->storeImplementationServiceId = $storeImplementationServiceId;
        $this->databaseAdapterServiceId     = $databaseAdapterServiceId;
    }
}
