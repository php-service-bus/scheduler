<?php

/**
 * Scheduler implementation.
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Scheduler\Store;

use function Amp\asyncCall;
use function Amp\call;
use function ServiceBus\Common\datetimeToString;
use function ServiceBus\Storage\Sql\deleteQuery;
use function ServiceBus\Storage\Sql\equalsCriteria;
use function ServiceBus\Storage\Sql\fetchOne;
use function ServiceBus\Storage\Sql\insertQuery;
use function ServiceBus\Storage\Sql\selectQuery;
use function ServiceBus\Storage\Sql\updateQuery;
use Amp\Promise;
use ServiceBus\Scheduler\Data\NextScheduledOperation;
use ServiceBus\Scheduler\Data\ScheduledOperation;
use ServiceBus\Scheduler\ScheduledOperationId;
use ServiceBus\Scheduler\Store\Exceptions\ScheduledOperationNotFound;
use ServiceBus\Storage\Common\BinaryDataDecoder;
use ServiceBus\Storage\Common\DatabaseAdapter;
use ServiceBus\Storage\Common\QueryExecutor;

/**
 *
 */
final class SqlSchedulerStore implements SchedulerStore
{
    private const TABLE_NAME = 'scheduler_registry';

    /**
     * @var DatabaseAdapter
     */
    private $adapter;

    /**
     * @param DatabaseAdapter $adapter
     */
    public function __construct(DatabaseAdapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * {@inheritdoc}
     */
    public function extract(ScheduledOperationId $id, callable $postExtract): Promise
    {
        /** @psalm-suppress InvalidArgument */
        return call(
            function(ScheduledOperationId $id) use ($postExtract): \Generator
            {
                /** @var ScheduledOperation|null $operation */
                $operation = yield from $this->load($this->adapter, $id);

                if (null === $operation)
                {
                    throw new ScheduledOperationNotFound(
                        \sprintf('Operation with ID "%s" not found', $id)
                    );
                }

                /**
                 * @psalm-suppress TooManyTemplateParams Invalid Promise template
                 *
                 * @var \ServiceBus\Storage\Common\Transaction $transaction
                 */
                $transaction = yield $this->adapter->transaction();

                try
                {
                    yield from $this->delete($transaction, $id);

                    /** @var NextScheduledOperation|null $nextOperation */
                    $nextOperation = yield from $this->fetchNextOperation($transaction);

                    /** @psalm-suppress InvalidArgument */
                    asyncCall($postExtract, $operation, $nextOperation);

                    yield $transaction->commit();
                }
                catch (\Throwable $throwable)
                {
                    yield $transaction->rollback();

                    /** @noinspection PhpUnhandledExceptionInspection */
                    throw $throwable;
                }
                finally
                {
                    unset($transaction);
                }
            },
            $id
        );
    }

    /**
     * {@inheritdoc}
     */
    public function remove(ScheduledOperationId $id, callable $postRemove): Promise
    {
        /** @psalm-suppress InvalidArgument */
        return call(
            function(ScheduledOperationId $id) use ($postRemove): \Generator
            {
                /**
                 * @psalm-suppress TooManyTemplateParams Invalid Promise template
                 *
                 * @var \ServiceBus\Storage\Common\Transaction $transaction
                 */
                $transaction = yield $this->adapter->transaction();

                try
                {
                    yield from $this->delete($transaction, $id);

                    /** @var NextScheduledOperation|null $nextOperation */
                    $nextOperation = yield from $this->fetchNextOperation($transaction);

                    /** @psalm-suppress InvalidArgument */
                    asyncCall($postRemove, $nextOperation);

                    yield $transaction->commit();
                }
                catch (\Throwable $throwable)
                {
                    yield $transaction->rollback();

                    /** @noinspection PhpUnhandledExceptionInspection */
                    throw $throwable;
                }
                finally
                {
                    unset($transaction);
                }
            },
            $id
        );
    }

    /**
     * {@inheritdoc}
     */
    public function add(ScheduledOperation $operation, callable $postAdd): Promise
    {
        /** @psalm-suppress InvalidArgument */
        return call(
            function(ScheduledOperation $operation) use ($postAdd): \Generator
            {
                /**
                 * @psalm-suppress TooManyTemplateParams Invalid Promise template
                 *
                 * @var \ServiceBus\Storage\Common\Transaction $transaction
                 */
                $transaction = yield $this->adapter->transaction();

                try
                {
                    $insertQuery = insertQuery('scheduler_registry', [
                        'id'              => (string) $operation->id,
                        'processing_date' => datetimeToString($operation->date),
                        'command'         => \base64_encode(\serialize($operation->command)),
                        'is_sent'         => (int) $operation->isSent,
                    ]);

                    $compiledQuery = $insertQuery->compile();

                    /**
                     * @psalm-suppress TooManyTemplateParams Invalid Promise template
                     * @psalm-suppress MixedTypeCoercion Invalid params() docblock
                     */
                    yield $transaction->execute($compiledQuery->sql(), $compiledQuery->params());

                    /**
                     * @psalm-suppress TooManyTemplateParams Invalid Promise template
                     *
                     * @var NextScheduledOperation|null $nextOperation
                     */
                    $nextOperation = yield from $this->fetchNextOperation($transaction);

                    /** @psalm-suppress InvalidArgument */
                    asyncCall($postAdd, $operation, $nextOperation);

                    yield $transaction->commit();
                }
                catch (\Throwable $throwable)
                {
                    yield $transaction->rollback();

                    /** @noinspection PhpUnhandledExceptionInspection */
                    throw $throwable;
                }
                finally
                {
                    unset($transaction);
                }
            },
            $operation
        );
    }

    /**
     *
     * @noinspection PhpDocMissingThrowsInspection
     *
     * @param QueryExecutor $queryExecutor
     *
     * @throws \ServiceBus\Storage\Common\Exceptions\ConnectionFailed
     * @throws \ServiceBus\Storage\Common\Exceptions\InvalidConfigurationOptions
     * @throws \ServiceBus\Storage\Common\Exceptions\ResultSetIterationFailed
     * @throws \ServiceBus\Storage\Common\Exceptions\StorageInteractingFailed
     *
     * @return \Generator
     */
    private function fetchNextOperation(QueryExecutor $queryExecutor): \Generator
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $selectQuery = selectQuery(self::TABLE_NAME)
            ->where(equalsCriteria('is_sent', 0))
            ->orderBy('processing_date', 'ASC')
            ->limit(1);

        $compiledQuery = $selectQuery->compile();

        /**
         * @psalm-suppress TooManyTemplateParams Invalid Promise template
         * @psalm-suppress MixedTypeCoercion Invalid params() docblock
         *
         * @var \ServiceBus\Storage\Common\ResultSet $resultSet
         */
        $resultSet = /** @noinspection PhpUnhandledExceptionInspection */
            yield $queryExecutor->execute($compiledQuery->sql(), $compiledQuery->params());

        /**
         * @psalm-suppress TooManyTemplateParams Invalid Promise template
         * @psalm-var      array<string, string>|null $result
         */
        $result = /** @noinspection PhpUnhandledExceptionInspection */
            yield fetchOne($resultSet);

        if (true === \is_array($result) && 0 !== \count($result))
        {
            /** @var int $affectedRows */
            $affectedRows = yield from $this->updateBarrierFlag($queryExecutor, $result['id']);

            if (0 !== $affectedRows)
            {
                /** @noinspection PhpUnhandledExceptionInspection */
                return NextScheduledOperation::fromRow($result);
            }
        }
    }

    /**
     * Update task is_sent flag.
     *
     * @noinspection PhpDocMissingThrowsInspection
     *
     * @param QueryExecutor $queryExecutor
     * @param string        $id
     *
     * @throws \ServiceBus\Storage\Common\Exceptions\ConnectionFailed
     * @throws \ServiceBus\Storage\Common\Exceptions\InvalidConfigurationOptions
     * @throws \ServiceBus\Storage\Common\Exceptions\ResultSetIterationFailed
     * @throws \ServiceBus\Storage\Common\Exceptions\StorageInteractingFailed
     *
     * @return \Generator
     */
    private function updateBarrierFlag(QueryExecutor $queryExecutor, string $id): \Generator
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $updateQuery = updateQuery(self::TABLE_NAME, ['is_sent' => 1])
            ->where(equalsCriteria('id', $id))
            ->andWhere(equalsCriteria('is_sent', 0));

        $compiledQuery = $updateQuery->compile();

        /**
         * @psalm-suppress TooManyTemplateParams Invalid Promise template
         * @psalm-suppress MixedTypeCoercion Invalid params() docblock
         *
         * @var \ServiceBus\Storage\Common\ResultSet $resultSet
         */
        $resultSet = /** @noinspection PhpUnhandledExceptionInspection */
            yield $queryExecutor->execute($compiledQuery->sql(), $compiledQuery->params());

        return $resultSet->affectedRows();
    }

    /**
     * Load scheduled operation.
     *
     * @noinspection PhpDocMissingThrowsInspection
     *
     * @param QueryExecutor        $queryExecutor
     * @param ScheduledOperationId $id
     *
     * @throws \ServiceBus\Scheduler\Exceptions\UnserializeCommandFailed
     * @throws \ServiceBus\Storage\Common\Exceptions\ConnectionFailed
     * @throws \ServiceBus\Storage\Common\Exceptions\InvalidConfigurationOptions
     * @throws \ServiceBus\Storage\Common\Exceptions\ResultSetIterationFailed
     * @throws \ServiceBus\Storage\Common\Exceptions\StorageInteractingFailed
     *
     * @return \Generator
     */
    private function load(QueryExecutor $queryExecutor, ScheduledOperationId $id): \Generator
    {
        $operation = null;

        /** @noinspection PhpUnhandledExceptionInspection */
        $selectQuery   = selectQuery(self::TABLE_NAME)->where(equalsCriteria('id', $id));
        $compiledQuery = $selectQuery->compile();

        /**
         * @psalm-suppress TooManyTemplateParams Invalid Promise template
         * @psalm-suppress MixedTypeCoercion Invalid params() docblock
         *
         * @var \ServiceBus\Storage\Common\ResultSet $resultSet
         */
        $resultSet = /** @noinspection PhpUnhandledExceptionInspection */
            yield $queryExecutor->execute($compiledQuery->sql(), $compiledQuery->params());

        /** @psalm-var array{processing_date:string, command:string, id:string, is_sent:bool}|null $result */
        $result = /** @noinspection PhpUnhandledExceptionInspection */
            yield fetchOne($resultSet);

        if (true === \is_array($result) && 0 !== \count($result))
        {
            if ($queryExecutor instanceof BinaryDataDecoder)
            {
                $result['command'] = $queryExecutor->unescapeBinary($result['command']);
            }

            /** @noinspection PhpUnhandledExceptionInspection */
            $operation = ScheduledOperation::restoreFromRow($result);
        }

        return $operation;
    }

    /**
     * Delete scheduled operation.
     *
     * @noinspection PhpDocMissingThrowsInspection
     *
     * @param QueryExecutor        $queryExecutor
     * @param ScheduledOperationId $id
     *
     * @throws \ServiceBus\Storage\Common\Exceptions\ConnectionFailed
     * @throws \ServiceBus\Storage\Common\Exceptions\IncorrectParameterCast
     * @throws \ServiceBus\Storage\Common\Exceptions\InvalidConfigurationOptions
     * @throws \ServiceBus\Storage\Common\Exceptions\StorageInteractingFailed
     *
     * @return \Generator
     */
    private function delete(QueryExecutor $queryExecutor, ScheduledOperationId $id): \Generator
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $deleteQuery   = deleteQuery(self::TABLE_NAME)->where(equalsCriteria('id', $id));
        $compiledQuery = $deleteQuery->compile();

        /**
         * @psalm-suppress TooManyTemplateParams Invalid Promise template
         * @psalm-suppress MixedTypeCoercion Invalid params() docblock
         * @noinspection   PhpUnhandledExceptionInspection
         */
        yield $queryExecutor->execute($compiledQuery->sql(), $compiledQuery->params());
    }
}
