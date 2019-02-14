<?php

/**
 * Scheduler implementation
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Scheduler\Processor;

use Amp\Failure;
use Amp\Promise;
use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Common\Messages\Message;
use ServiceBus\Scheduler\Contract\EmitSchedulerOperation;
use ServiceBus\Scheduler\Contract\OperationScheduled;
use ServiceBus\Scheduler\Contract\SchedulerOperationCanceled;
use ServiceBus\Scheduler\Contract\SchedulerOperationEmitted;
use ServiceBus\Scheduler\Emitter\SchedulerEmitter;

/**
 * Scheduler listener\command handler
 */
final class SchedulerMessagesProcessor
{
    /**
     * @var SchedulerEmitter
     */
    private $emitter;

    /**
     * @param SchedulerEmitter $emitter
     */
    public function __construct(SchedulerEmitter $emitter)
    {
        $this->emitter = $emitter;
    }

    /**
     * Execute message
     *
     * @noinspection PhpDocRedundantThrowsInspection
     *
     * @param Message           $message
     * @param ServiceBusContext $context
     *
     * @return Promise
     *
     * @throws \LogicException Unsupported message type specified
     * @throws \ServiceBus\Scheduler\Exceptions\EmitFailed
     */
    public function handle(Message $message, ServiceBusContext $context): Promise
    {
        if($message instanceof EmitSchedulerOperation)
        {
            return $this->emitter->emit($message->id, $context);
        }

        if(
            true === ($message instanceof SchedulerOperationEmitted) ||
            true === ($message instanceof SchedulerOperationCanceled) ||
            true === ($message instanceof OperationScheduled)
        )
        {
            /** @var SchedulerOperationEmitted|SchedulerOperationCanceled|OperationScheduled $message */

            return $this->emitter->emitNextOperation($message->nextOperation, $context);
        }

        return new Failure(
            new \LogicException(\sprintf('Unsupported message type specified (%s)', \get_class($message)))
        );
    }
}
