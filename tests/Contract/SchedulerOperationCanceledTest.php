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
     */
    public function create(): void
    {
        $id = ScheduledOperationId::new();

        $operation = new SchedulerOperationCanceled($id, 'test');

        self::assertSame($id, $operation->id);
        self::assertSame('test', $operation->reason);
        self::assertNull($operation->nextOperation);
    }
}
