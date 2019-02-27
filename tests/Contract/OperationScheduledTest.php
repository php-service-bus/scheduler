<?php

/**
 * Scheduler implementation.
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Scheduler\Tests\Contract;

use PHPUnit\Framework\TestCase;
use ServiceBus\Scheduler\Contract\OperationScheduled;
use ServiceBus\Scheduler\Data\NextScheduledOperation;
use ServiceBus\Scheduler\ScheduledOperationId;

/**
 *
 */
final class OperationScheduledTest extends TestCase
{
    /**
     * @test
     *
     * @throws \Throwable
     *
     * @return void
     *
     */
    public function create(): void
    {
        $command = new class()
        {
        };

        $id       = ScheduledOperationId::restore('qwerty');
        $dateTime = new \DateTimeImmutable('NOW');
        $next     = NextScheduledOperation::create($id, $dateTime);

        $operation = OperationScheduled::create($id, $command, $dateTime, $next);

        static::assertNotNull($operation->nextOperation);

        static::assertSame($id, $operation->id);
        static::assertSame($id, $operation->nextOperation->id);

        static::assertSame($dateTime->format('c'), $operation->executionDate->format('c'));
        static::assertSame($dateTime->format('c'), $operation->nextOperation->time->format('c'));

        static::assertSame(\get_class($command), $operation->commandNamespace);
    }
}
