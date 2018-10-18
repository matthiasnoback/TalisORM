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
     * @param Aggregate $aggregate
     * @throws ConcurrentUpdateOccurred (only when you use optimistic concurrency locking)
     * @return void
     */
    public function save(Aggregate $aggregate)
    {
        $this->connection->transactional(function () use ($aggregate) {
            $this->insertOrUpdate($aggregate);

            foreach ($aggregate->deletedChildEntities() as $childEntity) {
                $this->connection->delete(
                    $this->connection->quoteIdentifier($childEntity::tableName()),
                    $childEntity->identifier()
                );
            }

            foreach ($aggregate->childEntitiesByType() as $type => $childEntities) {
                foreach ($childEntities as $childEntity) {
                    $this->insertOrUpdate($childEntity);
                }
            }
        });

        $this->eventDispatcher->dispatch($aggregate->releaseEvents());
    }

    /**
     * @param string $aggregateClass
     * @param AggregateId $aggregateId
     * @return Aggregate
     */
    public function getById($aggregateClass, AggregateId $aggregateId)
    {
        Assert::string($aggregateClass);

        if (!is_a($aggregateClass, Aggregate::class, true)) {
            throw new InvalidArgumentException(sprintf(
                'Class "%s" has to implement "%s"',
                $aggregateClass,
                Aggregate::class
            ));
        }

        $aggregateState = $this->getAggregateState($aggregateClass, $aggregateId);

        $childEntitiesByType = $this->getChildEntitiesByType($aggregateClass, $aggregateId);

        $aggregate = $aggregateClass::fromState($aggregateState, $childEntitiesByType);

        if (!$aggregate instanceof $aggregateClass || !$aggregate instanceof Aggregate) {
            throw new LogicException(sprintf(
                'Method "%s::fromState()" was expected to return an instance of "%1$s"',
                $aggregateClass
            ));
        }

        return $aggregate;
    }

    /**
     * @param string $aggregateClass
     * @param AggregateId $aggregateId
     * @return array
     */
    private function getAggregateState($aggregateClass, AggregateId $aggregateId)
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
     * @param string $aggregateClass
     * @param AggregateId $aggregateId
     * @return array[]
     */
    private function getChildEntitiesByType($aggregateClass, AggregateId $aggregateId)
    {
        $childEntitiesByType = [];

        foreach ($aggregateClass::childEntityTypes() as $childEntityType) {
            $childEntityStates = $this->fetchAll(
                $childEntityType::tableName(),
                $childEntityType::identifierForQuery($aggregateId)
            );

            $childEntitiesByType[$childEntityType] = array_map(
                function (array $childEntityState) use ($childEntityType) {
                    return $childEntityType::fromState($childEntityState);
                },
                $childEntityStates
            );
        }

        return $childEntitiesByType;
    }

    public function delete(Aggregate $aggregate)
    {
        $this->connection->transactional(function () use ($aggregate) {
            $this->connection->delete(
                $this->connection->quoteIdentifier($aggregate::tableName()),
                $aggregate->identifier()
            );

            foreach ($aggregate->childEntitiesByType() as $type => $childEntities) {
                foreach ($childEntities as $childEntity) {
                    /** @var ChildEntity $childEntity */
                    $this->connection->delete(
                        $this->connection->quoteIdentifier($childEntity::tableName()),
                        $childEntity->identifier()
                    );
                }
            }
        });
    }

    private function insertOrUpdate(Entity $entity)
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
     * @param string $tableName
     * @param array $identifier
     * @return array[]
     */
    private function fetchAll($tableName, array $identifier)
    {
        Assert::string($tableName);

        return $this->select('*', $tableName, $identifier)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * This method might have been on Connection itself...
     *
     * @param string $selectExpression
     * @param string $tableExpression
     * @param array $where
     * @return ResultStatement
     */
    private function select($selectExpression, $tableExpression, array $where)
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
