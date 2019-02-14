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
use ServiceBus\Scheduler\Contract\SchedulerOperationEmitted;
use ServiceBus\Scheduler\ScheduledOperationId;

/**
 *
 */
final class SchedulerOperationEmittedTest extends TestCase
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

        $operation = SchedulerOperationEmitted::create($id);

        static::assertEquals($id, $operation->id);
        static::assertNull($operation->nextOperation);
    }
}
