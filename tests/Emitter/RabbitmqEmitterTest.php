<?php

declare(strict_types=1);

namespace Emitter;

use PHPUnit\Framework\TestCase;
use ServiceBus\Scheduler\Data\NextScheduledOperation;
use ServiceBus\Scheduler\Emitter\RabbitMQEmitter;
use ServiceBus\Scheduler\ScheduledOperationId;
use ServiceBus\Scheduler\Store\SqlSchedulerStore;
use ServiceBus\Scheduler\Tests\Context;
use ServiceBus\Storage\Common\StorageConfiguration;
use ServiceBus\Storage\Sql\DoctrineDBAL\DoctrineDBALAdapter;

final class RabbitmqEmitterTest extends TestCase
{
    /**
     * @test
     *
     * @dataProvider isHighestPriorityOptionProvider
     */
    public function isHighestPriorityOption(bool $expectedFlag, ?bool $optionFlag): void
    {
        $emitter = new RabbitMQEmitter(
            new SqlSchedulerStore(
                new DoctrineDBALAdapter(new StorageConfiguration('sqlite:///:memory:')),
            ),
            $optionFlag,
        );

        $context = new Context();

        $emitter->emitNextOperation(
            NextScheduledOperation::create(ScheduledOperationId::new(), new \DateTimeImmutable()),
            $context,
        );

        $this->assertSame($expectedFlag, $context->deliveryOptions->isHighestPriority());
    }

    public static function isHighestPriorityOptionProvider(): \Generator
    {
        yield 'true option results in true flag' => ['expectedFlag' => true, 'optionFlag' => true];
        yield 'false option results in false flag' => ['expectedFlag' => false, 'optionFlag' => false];
        yield 'null option results in default true flag' => ['expectedFlag' => true, 'optionFlag' => null];
    }
}
