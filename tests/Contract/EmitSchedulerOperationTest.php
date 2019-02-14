<?php

/**
 * Common scheduler implementation
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Scheduler\Common\Tests\Contract;

use PHPUnit\Framework\TestCase;
use ServiceBus\Scheduler\Common\Contract\EmitSchedulerOperation;
use ServiceBus\Scheduler\Common\ScheduledOperationId;

/**
 *
 */
final class EmitSchedulerOperationTest extends TestCase
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
        $id = ScheduledOperationId::new();

        EmitSchedulerOperation::create($id);

        static::assertEquals($id, EmitSchedulerOperation::create($id)->id);
    }
}
