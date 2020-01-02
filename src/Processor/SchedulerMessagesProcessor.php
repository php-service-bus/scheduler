<?php

/**
 * Scheduler implementation.
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
use ServiceBus\Common\MessageExecutor\MessageExecutor;
use ServiceBus\Scheduler\Contract\EmitSchedulerOperation;
use ServiceBus\Scheduler\Contract\OperationScheduled;
use ServiceBus\Scheduler\Contract\SchedulerOperationCanceled;
use ServiceBus\Scheduler\Contract\SchedulerOperationEmitted;
use ServiceBus\Scheduler\Emitter\SchedulerEmitter;

/**
 * Scheduler listener\command handler.
 */
final class SchedulerMessagesProcessor implements MessageExecutor
{
    /** @var SchedulerEmitter */
    private $emitter;

    public function __construct(SchedulerEmitter $emitter)
    {
        $this->emitter = $emitter;
    }

    /**
     * @inheritDoc
     *
     * @noinspection PhpDocRedundantThrowsInspection
     *
     * @throws \LogicException Unsupported message type specified
     * @throws \ServiceBus\Scheduler\Exceptions\EmitFailed
     */
    public function __invoke(object $message, ServiceBusContext $context): Promise
    {
        if ($message instanceof EmitSchedulerOperation)
        {
            return $this->emitter->emit($message->id, $context);
        }

        if (
            ($message instanceof SchedulerOperationEmitted) === true ||
            ($message instanceof SchedulerOperationCanceled) === true ||
            ($message instanceof OperationScheduled) === true
        ) {
            /** @var OperationScheduled|SchedulerOperationCanceled|SchedulerOperationEmitted $message */

            return $this->emitter->emitNextOperation($message->nextOperation, $context);
        }

        return new Failure(
            new \LogicException(\sprintf('Unsupported message type specified (%s)', \get_class($message)))
        );
    }
}
