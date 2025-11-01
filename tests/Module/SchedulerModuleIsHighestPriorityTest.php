<?php

declare(strict_types=1);

namespace Module;

use PHPUnit\Framework\TestCase;
use ServiceBus\Scheduler\Emitter\RabbitMQEmitter;
use ServiceBus\Scheduler\Emitter\SchedulerEmitter;
use ServiceBus\Scheduler\Module\SchedulerModule;
use ServiceBus\Scheduler\Store\SqlSchedulerStore;
use ServiceBus\Storage\Common\DatabaseAdapter;
use ServiceBus\Storage\Common\StorageConfiguration;
use ServiceBus\Storage\Sql\DoctrineDBAL\DoctrineDBALAdapter;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class SchedulerModuleIsHighestPriorityTest extends TestCase
{
    private ContainerBuilder $containerBuilder;

    protected function setUp(): void
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->addDefinitions([
            DatabaseAdapter::class => new Definition(
                DoctrineDBALAdapter::class,
                [
                    new StorageConfiguration('sqlite:///:memory:'),
                ],
            ),
        ]);

        $this->containerBuilder = $containerBuilder;
    }

    /**
     * To keep thing backward compatible for the clients who use default highestPriority = true
     *
     * @test
     */
    public function highestPriorityIsDefault(): void
    {
        $module = SchedulerModule::rabbitMqWithSqlStorage(DatabaseAdapter::class);
        $module->boot($this->containerBuilder);

        $this->assertIsHighestPriority(true);
    }

    /**
     * @test
     */
    public function highestPriorityCanBeOverriden(): void
    {
        $module = SchedulerModule::rabbitMqWithSqlStorage(DatabaseAdapter::class);
        $module->boot($this->containerBuilder);

        $this->containerBuilder->setParameter('service_bus.scheduler.deliver_with_highest_priority', false);

        $this->assertIsHighestPriority(false);
    }

    private function assertIsHighestPriority(?bool $isHighestPriority): void
    {
        self::assertEquals(
            new RabbitMQEmitter(
                new SqlSchedulerStore(
                    new DoctrineDBALAdapter(new StorageConfiguration('sqlite:///:memory:')),
                ),
                $isHighestPriority,
            ),
            $this->containerBuilder->get(SchedulerEmitter::class),
        );
    }
}
