<?php

/**
 * Common scheduler implementation interfaces
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Scheduler\Common\Tests\Contract;

use PHPUnit\Framework\TestCase;
use ServiceBus\Scheduler\Common\Contract\SchedulerOperationEmitted;
use ServiceBus\Scheduler\Common\ScheduledOperationId;

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
