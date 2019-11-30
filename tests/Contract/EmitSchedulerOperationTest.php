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
     *
     * @return void
     *
     */
    public function create(): void
    {
        $id = ScheduledOperationId::new();

        new EmitSchedulerOperation($id);

        static::assertSame($id, (new EmitSchedulerOperation($id))->id);
    }
}
