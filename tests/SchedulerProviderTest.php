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

use function Amp\Promise\wait;
use function ServiceBus\Storage\Sql\fetchOne;
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
     * @throws \Throwable
     *
     * @return void
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
     * @throws \Throwable
     *
     * @return void
     */
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        self::$adapter = null;
    }

    /**
     * @throws \Throwable
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->store    = new SqlSchedulerStore(self::$adapter);
        $this->provider = new SchedulerProvider($this->store);
    }

    /**
     * @throws \Throwable
     *
     * @return void
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
     * @throws \Throwable
     *
     * @return void
     */
    public function scheduleWithWrongDate(): void
    {
        $this->expectException(InvalidScheduledOperationExecutionDate::class);
        $this->expectExceptionMessage('The date of the scheduled task should be greater than the current one');

        wait(
            $this->provider->schedule(
                ScheduledOperationId::new(),
                new EmptyCommand(),
                new \DateTimeImmutable('-1 days'),
                new Context()
            )
        );
    }

    /**
     * @test
     *
     * @throws \Throwable
     *
     * @return void
     */
    public function successSchedule(): void
    {
        $context = new Context();

        wait(
            $this->provider->schedule(
                ScheduledOperationId::new(),
                new EmptyCommand(),
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
     * @throws \Throwable
     *
     * @return void
     */
    public function scheduleDuplicateOperation(): void
    {
        $this->expectException(DuplicateScheduledOperation::class);

        $id      = ScheduledOperationId::new();
        $context = new Context();

        wait(
            $this->provider->schedule(
                $id,
                new EmptyCommand(),
                new \DateTimeImmutable('+1 days'),
                $context
            )
        );

        wait(
            $this->provider->schedule(
                $id,
                new EmptyCommand(),
                new \DateTimeImmutable('+1 days'),
                $context
            )
        );
    }

    /**
     * @test
     *
     * @throws \Throwable
     *
     * @return void
     */
    public function cancelScheduledOperation(): void
    {
        $id      = ScheduledOperationId::new();
        $context = new Context();

        wait(
            $this->provider->schedule(
                $id,
                new EmptyCommand(),
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

        static::assertSame(0, (int) $operations['cnt']);
    }

    /**
     * @test
     *
     * @throws \Throwable
     *
     * @return void
     */
    public function cancelUnknownOperation(): void
    {
        $context = new Context();

        wait($this->provider->cancel(ScheduledOperationId::new(), $context));

        static::assertCount(1, $context->messages);
    }
}
