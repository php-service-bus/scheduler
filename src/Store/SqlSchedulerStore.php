<?php /** @noinspection PhpUnhandledExceptionInspection */

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

    /** @var DatabaseAdapter */
    private $adapter;

    public function __construct(DatabaseAdapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * {@inheritdoc}
     */
    public function extract(ScheduledOperationId $id, callable $postExtract): Promise
    {
        return call(
            function () use ($id, $postExtract): \Generator
            {
                /** @var ScheduledOperation|null $operation */
                $operation = yield from self::load($this->adapter, $id);

                if ($operation === null)
                {
                    throw new ScheduledOperationNotFound(
                        \sprintf('Operation with ID "%s" not found', $id->toString())
                    );
                }

                /** @var \ServiceBus\Storage\Common\Transaction $transaction */
                $transaction = yield $this->adapter->transaction();

                try
                {
                    yield from self::delete($transaction, $id);

                    /** @var NextScheduledOperation|null $nextOperation */
                    $nextOperation = yield from self::fetchNextOperation($transaction);

                    /** @psalm-suppress InvalidArgument */
                    asyncCall($postExtract, $operation, $nextOperation);

                    yield $transaction->commit();
                }
                catch (\Throwable $throwable)
                {
                    yield $transaction->rollback();

                    throw $throwable;
                }
                finally
                {
                    unset($transaction);
                }
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function remove(ScheduledOperationId $id, callable $postRemove): Promise
    {
        return call(
            function () use ($id, $postRemove): \Generator
            {
                /** @var \ServiceBus\Storage\Common\Transaction $transaction */
                $transaction = yield  $this->adapter->transaction();

                try
                {
                    yield from self::delete($transaction, $id);

                    /** @var NextScheduledOperation|null $nextOperation */
                    $nextOperation = yield from self::fetchNextOperation($transaction);

                    /** @psalm-suppress InvalidArgument */
                    asyncCall($postRemove, $nextOperation);

                    yield $transaction->commit();
                }
                catch (\Throwable $throwable)
                {
                    yield $transaction->rollback();

                    throw $throwable;
                }
                finally
                {
                    unset($transaction);
                }
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function add(ScheduledOperation $operation, callable $postAdd): Promise
    {
        /** @psalm-suppress InvalidArgument */
        return call(
            function () use ($operation, $postAdd): \Generator
            {
                /** @var \ServiceBus\Storage\Common\Transaction $transaction */
                $transaction = yield  $this->adapter->transaction();

                try
                {
                    $insertQuery = insertQuery('scheduler_registry', [
                        'id'              => $operation->id->toString(),
                        'processing_date' => $operation->date->format('Y-m-d H:i:s.u'),
                        'command'         => \base64_encode(\serialize($operation->command)),
                        'is_sent'         => (int) $operation->isSent,
                    ]);

                    $compiledQuery = $insertQuery->compile();

                    /** @psalm-suppress MixedTypeCoercion Invalid params() docblock */
                    yield $transaction->execute($compiledQuery->sql(), $compiledQuery->params());

                    /** @var NextScheduledOperation|null $nextOperation */
                    $nextOperation = yield from self::fetchNextOperation($transaction);

                    /** @psalm-suppress InvalidArgument */
                    asyncCall($postAdd, $operation, $nextOperation);

                    yield $transaction->commit();
                }
                catch (\Throwable $throwable)
                {
                    yield $transaction->rollback();

                    throw $throwable;
                }
                finally
                {
                    unset($transaction);
                }
            }
        );
    }

    /**
     * @noinspection PhpDocMissingThrowsInspection
     *
     * @throws \ServiceBus\Storage\Common\Exceptions\ConnectionFailed
     * @throws \ServiceBus\Storage\Common\Exceptions\InvalidConfigurationOptions
     * @throws \ServiceBus\Storage\Common\Exceptions\ResultSetIterationFailed
     * @throws \ServiceBus\Storage\Common\Exceptions\StorageInteractingFailed
     */
    private static function fetchNextOperation(QueryExecutor $queryExecutor): \Generator
    {
        /**
         * @noinspection PhpUnhandledExceptionInspection
         *
         * @var \Latitude\QueryBuilder\Query\SelectQuery $selectQuery
         */
        $selectQuery = selectQuery(self::TABLE_NAME)
            ->where(equalsCriteria('is_sent', 0))
            ->orderBy('processing_date', 'ASC')
            ->limit(1);

        $compiledQuery = $selectQuery->compile();

        /**
         * @psalm-suppress MixedTypeCoercion Invalid params() docblock
         *
         * @var \ServiceBus\Storage\Common\ResultSet $resultSet
         */
        $resultSet = yield $queryExecutor->execute($compiledQuery->sql(), $compiledQuery->params());

        /** @psalm-var      array<string, string>|null $result */
        $result = yield fetchOne($resultSet);

        if (\is_array($result) === true && \count($result) !== 0)
        {
            /** @var int $affectedRows */
            $affectedRows = yield from self::updateBarrierFlag($queryExecutor, $result['id']);

            if ($affectedRows !== 0)
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
     * @throws \ServiceBus\Storage\Common\Exceptions\ConnectionFailed
     * @throws \ServiceBus\Storage\Common\Exceptions\InvalidConfigurationOptions
     * @throws \ServiceBus\Storage\Common\Exceptions\ResultSetIterationFailed
     * @throws \ServiceBus\Storage\Common\Exceptions\StorageInteractingFailed
     */
    private static function updateBarrierFlag(QueryExecutor $queryExecutor, string $id): \Generator
    {
        /**
         * @noinspection PhpUnhandledExceptionInspection
         *
         * @var \Latitude\QueryBuilder\Query\UpdateQuery $updateQuery
         */
        $updateQuery = updateQuery(self::TABLE_NAME, ['is_sent' => 1])
            ->where(equalsCriteria('id', $id))
            ->andWhere(equalsCriteria('is_sent', 0));

        $compiledQuery = $updateQuery->compile();

        /**
         * @psalm-suppress MixedTypeCoercion Invalid params() docblock
         *
         * @var \ServiceBus\Storage\Common\ResultSet $resultSet
         */
        $resultSet = yield $queryExecutor->execute($compiledQuery->sql(), $compiledQuery->params());

        return $resultSet->affectedRows();
    }

    /**
     * Load scheduled operation.
     *
     * @noinspection PhpDocMissingThrowsInspection
     *
     * @throws \ServiceBus\Scheduler\Exceptions\UnserializeCommandFailed
     * @throws \ServiceBus\Storage\Common\Exceptions\ConnectionFailed
     * @throws \ServiceBus\Storage\Common\Exceptions\InvalidConfigurationOptions
     * @throws \ServiceBus\Storage\Common\Exceptions\ResultSetIterationFailed
     * @throws \ServiceBus\Storage\Common\Exceptions\StorageInteractingFailed
     */
    private static function load(QueryExecutor $queryExecutor, ScheduledOperationId $id): \Generator
    {
        $operation = null;

        /** @noinspection PhpUnhandledExceptionInspection */
        $selectQuery   = selectQuery(self::TABLE_NAME)->where(equalsCriteria('id', $id->toString()));
        $compiledQuery = $selectQuery->compile();

        /**
         * @psalm-suppress MixedTypeCoercion Invalid params() docblock
         *
         * @var \ServiceBus\Storage\Common\ResultSet $resultSet
         */
        $resultSet = yield $queryExecutor->execute($compiledQuery->sql(), $compiledQuery->params());

        /** @psalm-var array{processing_date:string, command:string, id:string, is_sent:bool}|null $result */
        $result = yield fetchOne($resultSet);

        if (\is_array($result) === true && \count($result) !== 0)
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
     * @throws \ServiceBus\Storage\Common\Exceptions\ConnectionFailed
     * @throws \ServiceBus\Storage\Common\Exceptions\IncorrectParameterCast
     * @throws \ServiceBus\Storage\Common\Exceptions\InvalidConfigurationOptions
     * @throws \ServiceBus\Storage\Common\Exceptions\StorageInteractingFailed
     */
    private static function delete(QueryExecutor $queryExecutor, ScheduledOperationId $id): \Generator
    {
        $deleteQuery   = deleteQuery(self::TABLE_NAME)->where(equalsCriteria('id', $id->toString()));
        $compiledQuery = $deleteQuery->compile();

        /** @psalm-suppress MixedTypeCoercion Invalid params() docblock */
        yield $queryExecutor->execute($compiledQuery->sql(), $compiledQuery->params());
    }
}
