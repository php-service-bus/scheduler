<?php

/**
 * Scheduler implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Scheduler\Processor;

use Amp\Promise;
use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Common\EntryPoint\Retry\RetryStrategy;
use ServiceBus\Common\MessageExecutor\MessageExecutor;
use ServiceBus\Scheduler\Contract\EmitSchedulerOperation;
use ServiceBus\Scheduler\Contract\OperationScheduled;
use ServiceBus\Scheduler\Contract\SchedulerOperationCanceled;
use ServiceBus\Scheduler\Contract\SchedulerOperationEmitted;
use ServiceBus\Scheduler\Emitter\SchedulerEmitter;
use function Amp\call;

/**
 * Scheduler listener\command handler.
 */
final class SchedulerMessagesProcessor implements MessageExecutor
{
    /**
     * @var SchedulerEmitter
     */
    private $emitter;

    public function __construct(SchedulerEmitter $emitter)
    {
        $this->emitter = $emitter;
    }

    public function id(): string
    {
        /** @psalm-var non-empty-string $id */
        $id = \sha1(\sprintf('%s:%s', __CLASS__, __METHOD__));

        return $id;
    }

    public function retryStrategy(): ?RetryStrategy
    {
        return null;
    }

    /**
     * @throws \LogicException Unsupported message type specified
     * @throws \ServiceBus\Scheduler\Exceptions\EmitFailed
     */
    public function __invoke(object $message, ServiceBusContext $context): Promise
    {
        return call(
            function() use ($message, $context): \Generator
            {
                if($message instanceof EmitSchedulerOperation)
                {
                    yield $this->emitter->emit($message->id, $context);
                }
                else if(
                    $message instanceof SchedulerOperationEmitted ||
                    $message instanceof SchedulerOperationCanceled ||
                    $message instanceof OperationScheduled
                )
                {
                    yield $this->emitter->emitNextOperation($message->nextOperation, $context);
                }
                else
                {
                    throw new \LogicException(
                        \sprintf('Unsupported message type specified (%s)', \get_class($message))
                    );
                }
            }
        );
    }
}
