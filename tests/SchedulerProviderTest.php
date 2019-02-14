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

use function Amp\Promise\wait;
use PHPUnit\Framework\TestCase;
use ServiceBus\Scheduler\Contract\OperationScheduled;
use ServiceBus\Scheduler\Contract\SchedulerOperationCanceled;
use ServiceBus\Scheduler\Exceptions\DuplicateScheduledOperation;
use ServiceBus\Scheduler\Exceptions\InvalidScheduledOperationExecutionDate;
use ServiceBus\Scheduler\ScheduledOperationId;
use ServiceBus\Scheduler\SchedulerProvider;
use ServiceBus\Scheduler\Store\SchedulerStore;
use ServiceBus\Scheduler\Store\SqlSchedulerStore;
use ServiceBus\Storage\Common\DatabaseAdapter;
use ServiceBus\Storage\Common\StorageConfiguration;
use ServiceBus\Storage\Sql\DoctrineDBAL\DoctrineDBALAdapter;
use function ServiceBus\Storage\Sql\fetchOne;

/**
 *
 */
final class SchedulerProviderTest extends TestCase
{
    /**
     * @var DatabaseAdapter
     */
    private static $adapter;

    /**
     * @var SchedulerStore
     */
    private $store;

    /**
     * @var SchedulerProvider
     */
    private $provider;

    /**
     * @return void
     *
     * @throws \Throwable
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$adapter = new DoctrineDBALAdapter(new StorageConfiguration('sqlite:///:memory:'));

        wait(
            self::$adapter->execute(\file_get_contents(__DIR__ . '/../src/Store/schema/scheduler_registry.sql'))
        );
    }

    /**
     * @return void
     *
     * @throws \Throwable
     */
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        self::$adapter = null;
    }

    /**
     * @return void
     *
     * @throws \Throwable
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->store    = new SqlSchedulerStore(self::$adapter);
        $this->provider = new SchedulerProvider($this->store);
    }

    /**
     * @return void
     *
     * @throws \Throwable
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        wait(self::$adapter->execute('DELETE FROM scheduler_registry'));

        unset($this->store, $this->provider);
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function scheduleWithWrongDate(): void
    {
        $this->expectException(InvalidScheduledOperationExecutionDate::class);
        $this->expectExceptionMessage('The date of the scheduled task should be greater than the current one');

        wait(
            $this->provider->schedule(
                ScheduledOperationId::new(),
                new EmptyCommand,
                new \DateTimeImmutable('-1 days'),
                new Context()
            )
        );
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function successSchedule(): void
    {
        $context = new Context();

        wait(
            $this->provider->schedule(
                ScheduledOperationId::new(),
                new EmptyCommand,
                new \DateTimeImmutable('+1 days'),
                $context
            )
        );

        $messages = $context->messages;

        static::assertCount(1, $messages);

        /** @var OperationScheduled $message */
        $message = \end($messages);

        static::assertInstanceOf(OperationScheduled::class, $message);
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function scheduleDuplicateOperation(): void
    {
        $this->expectException(DuplicateScheduledOperation::class);

        $id      = ScheduledOperationId::new();
        $context = new Context();

        wait(
            $this->provider->schedule(
                $id,
                new EmptyCommand,
                new \DateTimeImmutable('+1 days'),
                $context
            )
        );

        wait(
            $this->provider->schedule(
                $id,
                new EmptyCommand,
                new \DateTimeImmutable('+1 days'),
                $context
            )
        );
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function cancelScheduledOperation(): void
    {
        $id      = ScheduledOperationId::new();
        $context = new Context();

        wait(
            $this->provider->schedule(
                $id,
                new EmptyCommand,
                new \DateTimeImmutable('+1 days'),
                $context
            )
        );

        wait($this->provider->cancel($id, $context));

        $messages = $context->messages;

        static::assertCount(2, $messages);

        /** @var SchedulerOperationCanceled $message */
        $message = \end($messages);

        static::assertInstanceOf(SchedulerOperationCanceled::class, $message);

        $resultSet  = wait(self::$adapter->execute('SELECT count(id) as cnt from scheduler_registry'));
        $operations = wait(fetchOne($resultSet));

        static::assertEquals(0, $operations['cnt']);
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function cancelUnknownOperation(): void
    {
        $context = new Context();

        wait($this->provider->cancel(ScheduledOperationId::new(), $context));

        static::assertCount(1, $context->messages);
    }
}
