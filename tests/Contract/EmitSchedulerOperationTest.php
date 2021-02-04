<?php

/**
 * Scheduler implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Scheduler\Tests\Contract;

use PHPUnit\Framework\TestCase;
use ServiceBus\Scheduler\Contract\EmitSchedulerOperation;
use ServiceBus\Scheduler\ScheduledOperationId;

/**
 *
 */
final class EmitSchedulerOperationTest extends TestCase
{
    /**
     * @test
     *
     * @throws \Throwable
     */
    public function create(): void
    {
        $id = ScheduledOperationId::new();

        self::assertSame($id, (new EmitSchedulerOperation($id))->id);
    }
}
