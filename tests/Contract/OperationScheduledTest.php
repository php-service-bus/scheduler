<?php

/**
 * Scheduler implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

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
     */
    public function create(): void
    {
        $command = new class ()
        {
        };

        $id       = ScheduledOperationId::restore('qwerty');
        $dateTime = new \DateTimeImmutable('NOW');
        $next     = NextScheduledOperation::create($id, $dateTime);

        $operation = new OperationScheduled($id, \get_class($command), $dateTime, $next);

        self::assertNotNull($operation->nextOperation);

        self::assertSame($id, $operation->id);
        self::assertSame($id, $operation->nextOperation->id);

        self::assertSame($dateTime->format('c'), $operation->executionDate->format('c'));
        self::assertSame($dateTime->format('c'), $operation->nextOperation->time->format('c'));

        self::assertSame(\get_class($command), $operation->commandNamespace);
    }
}
