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
use ServiceBus\Scheduler\Contract\SchedulerOperationCanceled;
use ServiceBus\Scheduler\ScheduledOperationId;

/**
 *
 */
final class SchedulerOperationCanceledTest extends TestCase
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
        $id = ScheduledOperationId::new();

        $operation = new SchedulerOperationCanceled($id, 'test');

        static::assertSame($id, $operation->id);
        static::assertSame('test', $operation->reason);
        static::assertNull($operation->nextOperation);
    }
}
