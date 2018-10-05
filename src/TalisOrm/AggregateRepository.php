<?php
declare(strict_types=1);

namespace TalisOrm;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\ResultStatement;
use InvalidArgumentException;
use function is_a;
use LogicException;
use PDO;
use TalisOrm\DomainEvents\EventDispatcher;
use TalisOrm\DomainEvents\RecordsDomainEvents;

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

    public function save(Aggregate $aggregate): void
    {
        $this->connection->transactional(function () use ($aggregate) {
            $this->insertOrUpdate($aggregate);

            foreach ($aggregate->deletedChildEntities() as $childEntity) {
                $this->connection->delete($childEntity->tableName(), $childEntity->identifier());
            }

            foreach ($aggregate->childEntitiesByType() as $type => $childEntities) {
                foreach ($childEntities as $childEntity) {
                    $this->insertOrUpdate($childEntity);
                }
            }
        });

        $this->eventDispatcher->dispatch(...$aggregate->releaseEvents());
    }

    public function getById(string $aggregateClass, AggregateId $aggregateId)
    {
        if (!is_a($aggregateClass, Aggregate::class, true)) {
            throw new InvalidArgumentException(sprintf(
                'Class "%s" has to implement "%s"',
                $aggregateClass,
                Aggregate::class
            ));
        }

        $aggregateStates = $this->fetchAll($aggregateClass::tableName(), $aggregateClass::identifierForQuery($aggregateId));
        if (\count($aggregateStates) === 0) {
            throw new AggregateNotFoundException(sprintf(
                'Could not find aggregate of type "%s" with id "%s"',
                $aggregateClass,
                $aggregateId
            ));
        }
        $aggregateState = reset($aggregateStates);

        $childEntityStatesByType = [];

        foreach ($aggregateClass::childEntityTypes() as $childEntityType) {
            $childEntityStatesByType = $this->fetchAll(
                $childEntityType::tableName(),
                $childEntityType::identifierForQuery($aggregateId)
            );

            $childEntityStatesByType[$childEntityType] = $childEntityStatesByType;
        }

        $aggregate = $aggregateClass::fromState($aggregateState, $childEntityStatesByType);

        if (!$aggregate instanceof $aggregateClass) {
            throw new LogicException(sprintf(
                'Method "%s::fromState()" was expected to return an instance of "%1$s"',
                $aggregateClass
            ));
        }

        return $aggregate;
    }

    public function delete(Aggregate $aggregate): void
    {
        $this->connection->transactional(function () use ($aggregate) {
            $this->connection->delete($aggregate->tableName(), $aggregate->identifier());

            foreach ($aggregate->childEntitiesByType() as $type => $childEntities) {
                foreach ($childEntities as $childEntity) {
                    $this->connection->delete($childEntity->tableName(), $childEntity->identifier());
                }
            }
        });
    }

    private function insertOrUpdate(Entity $entity): void
    {
        if ($this->exists($entity->tableName(), $entity->identifier())) {
            $this->connection->update($entity->tableName(), $entity->state(), $entity->identifier());
        } else {
            $this->connection->insert($entity->tableName(), $entity->state());
        }
    }

    private function exists(string $tableName, array $identifier): bool
    {
        $count = $this->select('COUNT(*)', $tableName, $identifier)->fetchColumn();

        return (int)$count > 0;
    }

    private function fetchAll(string $tableName, array $identifier): array
    {
        return $this->select('*', $tableName, $identifier)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * This method might have been on Connection itself...
     *
     * @param string $selectExpression
     * @param string $tableExpression
     * @param array $where
     * @return ResultStatement
     * @throws DBALException
     */
    private function select(string $selectExpression, string $tableExpression, array $where): ResultStatement
    {
        $conditions = [];
        $values = [];
        foreach ($where as $columnName => $value) {
            $conditions[] = $columnName . ' = ?';
            $values[] = $value;
        }

        $sql = 'SELECT ' . $selectExpression
            . ' FROM ' . $tableExpression
            . ' WHERE ' . implode(' AND ', $conditions);

        return $this->connection->executeQuery($sql, $values);
    }
}
