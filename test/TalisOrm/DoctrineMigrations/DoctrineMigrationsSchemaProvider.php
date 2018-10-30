<?php

namespace TalisOrm\DoctrineMigrations;

use Doctrine\DBAL\Migrations\Provider\SchemaProviderInterface;
use TalisOrm\Schema\AggregateSchemaProvider;

final class DoctrineMigrationsSchemaProvider implements SchemaProviderInterface
{
    /**
     * @var AggregateSchemaProvider
     */
    private $aggregateSchemaProvider;

    public function __construct(AggregateSchemaProvider $aggregateSchemaProvider)
    {
        $this->aggregateSchemaProvider = $aggregateSchemaProvider;
    }

    public function createSchema()
    {
        return $this->aggregateSchemaProvider->createSchema();
    }
}
