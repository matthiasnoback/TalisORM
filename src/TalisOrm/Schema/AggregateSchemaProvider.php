<?php

namespace TalisOrm\Schema;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use InvalidArgumentException;

final class AggregateSchemaProvider
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string[]
     */
    private $aggregateClasses;

    public function __construct(Connection $connection, array $aggregateClasses)
    {
        $this->connection = $connection;
        $this->aggregateClasses = $aggregateClasses;
    }

    /**
     * @return Schema
     */
    public function createSchema()
    {
        $schema = $this->connection->getSchemaManager()->createSchema();

        foreach ($this->aggregateClasses as $aggregateClass) {
            if (!is_a($aggregateClass, SpecifiesSchema::class, true)) {
                throw new InvalidArgumentException(sprintf(
                    'Class "%s" was expected to implement "%s"',
                    $aggregateClass,
                    SpecifiesSchema::class
                ));
            }

            $aggregateClass::specifySchema($schema);
        }

        return $schema;
    }
}
