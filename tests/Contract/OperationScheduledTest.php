<?php

/**
 * Scheduler implementation
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
     * @return void
     *
     * @throws \Throwable
     */
    public function create(): void
    {
        $command = new class
        {

        };

        $id       = ScheduledOperationId::restore('qwerty');
        $dateTime = new \DateTimeImmutable('NOW');
        $next     = NextScheduledOperation::create($id, $dateTime);

        $operation = OperationScheduled::create($id, $command, $dateTime, $next);

        static::assertNotNull($operation->nextOperation);

        static::assertEquals($id, $operation->id);
        static::assertEquals($id, $operation->nextOperation->id);

        static::assertEquals($dateTime->format('c'), $operation->executionDate->format('c'));
        static::assertEquals($dateTime->format('c'), $operation->nextOperation->time->format('c'));

        static::assertEquals(\get_class($command), $operation->commandNamespace);
    }
}
