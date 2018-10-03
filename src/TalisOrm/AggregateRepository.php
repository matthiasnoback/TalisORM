<?php
declare(strict_types=1);

namespace TalisOrm;

use function class_implements;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use InvalidArgumentException;
use function is_a;

final class AggregateRepository
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function save(Aggregate $aggregate)
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

        $queryBuilder = $this->createQueryBuilderFor(
            $aggregateClass::tableName(),
            $aggregateClass::identifierForQuery($aggregateId)
        )
            ->select('*');

        $states = [];

        $aggregateState = $queryBuilder->execute()->fetch(\PDO::FETCH_ASSOC);
        if ($aggregateState === false) {
            throw new AggregateNotFoundException(sprintf(
                'Could not find aggregate of type "%s" with id "%s"',
                $aggregateClass,
                $aggregateId
            ));
        }

        $states[] = $aggregateState;

        foreach ($aggregateClass::childEntityTypes() as $childEntityType) {
            $childEntityStates = $this
                ->createQueryBuilderFor(
                    $childEntityType::tableName(),
                    $childEntityType::identifierForQuery($aggregateId)
                )
                ->select('*')
                ->execute()
                ->fetchAll(\PDO::FETCH_ASSOC);

            $states[] = $childEntityStates;
        }

        return $aggregateClass::fromState(...$states);
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
        if ($this->entityExists($entity)) {
            $this->connection->update($entity->tableName(), $entity->state(), $entity->identifier());
        } else {
            $this->connection->insert($entity->tableName(), $entity->state());
        }
    }

    private function entityExists(Entity $entity): bool
    {
        $queryBuilder = $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from($entity->tableName());

        $identifier = $entity->identifier();
        foreach ($identifier as $column => $value) {
            $queryBuilder->andWhere($column . ' = ?');
        }
        $queryBuilder->setParameters(array_values($identifier));

        return (int)$queryBuilder->execute()->fetchColumn() > 0;
    }

    private function createQueryBuilderFor(string $tableName, array $identifier): QueryBuilder
    {
        $queryBuilder = $this->connection->createQueryBuilder()
            ->from($tableName);

        foreach ($identifier as $column => $value) {
            $queryBuilder->andWhere($column . ' = ?');
        }

        $queryBuilder->setParameters(array_values($identifier));

        return $queryBuilder;
    }
}
