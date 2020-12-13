<?php

namespace TalisOrm;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use InvalidArgumentException;
use function is_a;
use LogicException;
use PDO;
use TalisOrm\DomainEvents\EventDispatcher;
use Webmozart\Assert\Assert;

/**
 * @phpstan-template T of Aggregate
 */
final class AggregateRepository
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    public function __construct(Connection $connection, EventDispatcher $eventDispatcher)
    {
        $this->connection = $connection;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @throws ConcurrentUpdateOccurred (only when you use optimistic concurrency locking)
     *
     * @phpstan-param T $aggregate
     */
    public function save(Aggregate $aggregate): void
    {
        $this->connection->transactional(function () use ($aggregate) {
            $persistedEntities = [];

            $this->insertOrUpdate($aggregate);
            $persistedEntities[] = $aggregate;

            foreach ($aggregate->deletedChildEntities() as $childEntity) {
                $this->connection->delete(
                    $this->connection->quoteIdentifier($childEntity::tableName()),
                    $childEntity->identifier()
                );
            }

            foreach ($aggregate->childEntitiesByType() as $type => $childEntities) {
                foreach ($childEntities as $childEntity) {
                    $this->insertOrUpdate($childEntity);
                    $persistedEntities[] = $childEntity;
                }
            }

            foreach ($persistedEntities as $persistedObject) {
                $persistedObject->markAsPersisted();
            }
        });

        $this->eventDispatcher->dispatch($aggregate->releaseEvents());
    }

    /**
     * @param array<string, mixed> $extraState
     * @return mixed|Aggregate
     *
     * @phpstan-param class-string<T> $aggregateClass
     * @phpstan-return T
     */
    public function getById(string $aggregateClass, AggregateId $aggregateId, array $extraState = []): Aggregate
    {
        if (!is_a($aggregateClass, Aggregate::class, true)) {
            throw new InvalidArgumentException(sprintf(
                'Class "%s" has to implement "%s"',
                $aggregateClass,
                Aggregate::class
            ));
        }

        $aggregateState = $this->getAggregateState($aggregateClass, $aggregateId);
        $aggregateState = array_merge($aggregateState, $extraState);

        $childEntitiesByType = $this->getChildEntitiesByType($aggregateClass, $aggregateId, $aggregateState);

        $aggregate = $aggregateClass::fromState($aggregateState, $childEntitiesByType);

        if (!$aggregate instanceof $aggregateClass || !$aggregate instanceof Aggregate) {
            throw new LogicException(sprintf(
                'Method "%s::fromState()" was expected to return an instance of "%1$s"',
                $aggregateClass
            ));
        }

        $aggregate->markAsPersisted();

        return $aggregate;
    }

    /**
     * @return array<string, mixed>
     *
     * @phpstan-param class-string<T> $aggregateClass
     */
    private function getAggregateState(string $aggregateClass, AggregateId $aggregateId): array
    {
        $aggregateStates = $this->fetchAll(
            $aggregateClass::tableName(),
            $aggregateClass::identifierForQuery($aggregateId)
        );

        if (\count($aggregateStates) === 0) {
            throw new AggregateNotFoundException(sprintf(
                'Could not find aggregate of type "%s" with id "%s"',
                $aggregateClass,
                $aggregateId
            ));
        }

        $aggregateState = reset($aggregateStates);

        Assert::isArray($aggregateState);

        return $aggregateState;
    }

    /**
     * @param array<string, mixed> $aggregateState
     * @return array<string, mixed[]>
     *
     * @phpstan-param class-string<T> $aggregateClass
     * @phpstan-return array<class-string<ChildEntity>, mixed[]>
     */
    private function getChildEntitiesByType(
        string $aggregateClass,
        AggregateId $aggregateId,
        array $aggregateState
    ): array {
        $childEntitiesByType = [];

        foreach ($aggregateClass::childEntityTypes() as $childEntityType) {
            $childEntityStates = $this->fetchAll(
                $childEntityType::tableName(),
                $childEntityType::identifierForQuery($aggregateId)
            );

            $childEntitiesByType[$childEntityType] = array_map(
                function (array $childEntityState) use ($childEntityType, $aggregateState) {
                    $childEntity = $childEntityType::fromState($childEntityState, $aggregateState);

                    $childEntity->markAsPersisted();

                    return $childEntity;
                },
                $childEntityStates
            );
        }

        return $childEntitiesByType;
    }

    /**
     * @phpstan-param T $aggregate
     */
    public function delete(Aggregate $aggregate): void
    {
        $this->connection->transactional(function () use ($aggregate) {
            $this->connection->delete(
                $this->connection->quoteIdentifier($aggregate::tableName()),
                $aggregate->identifier()
            );

            foreach ($aggregate->childEntitiesByType() as $type => $childEntities) {
                foreach ($childEntities as $childEntity) {
                    $this->connection->delete(
                        $this->connection->quoteIdentifier($childEntity::tableName()),
                        $childEntity->identifier()
                    );
                }
            }
        });
    }

    private function insertOrUpdate(Entity $entity): void
    {
        if ($entity->isNew()) {
            $this->connection->insert(
                $this->connection->quoteIdentifier($entity::tableName()),
                $entity->state()
            );
        } else {
            $state = $entity->state();
            if (array_key_exists(Aggregate::VERSION_COLUMN, $state)) {
                $aggregateVersion = $state[Aggregate::VERSION_COLUMN];
                $aggregateVersionInDb = (int)$this->select(
                    Aggregate::VERSION_COLUMN,
                    $entity->tableName(),
                    $entity->identifier()
                )->fetchColumn();
                if ($aggregateVersionInDb >= $aggregateVersion) {
                    throw ConcurrentUpdateOccurred::ofEntity($entity);
                }
            }
            $this->connection->update(
                $this->connection->quoteIdentifier($entity::tableName()),
                $state,
                $entity->identifier()
            );
        }
    }

    /**
     * @param array<string, mixed> $identifier
     * @return array<int, array<string, mixed>>
     */
    private function fetchAll(string $tableName, array $identifier): array
    {
        Assert::string($tableName);

        return $this->select('*', $tableName, $identifier)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * This method might have been on Connection itself...
     *
     * @param array<string, mixed> $where
     * @return ResultStatement<mixed>
     */
    private function select(string $selectExpression, string $tableExpression, array $where): ResultStatement
    {
        Assert::string($selectExpression);
        Assert::string($tableExpression);

        $conditions = [];
        $values = [];
        foreach ($where as $columnName => $value) {
            $conditions[] = $columnName . ' = ?';
            $values[] = $value;
        }

        $sql = 'SELECT ' . $selectExpression
            . ' FROM ' . $this->connection->quoteIdentifier($tableExpression)
            . ' WHERE ' . implode(' AND ', $conditions);

        return $this->connection->executeQuery($sql, $values);
    }
}
