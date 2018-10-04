<?php
declare(strict_types=1);

namespace TalisOrm\Schema;

use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use TalisOrm\Schema\AggregateSchemaProviderTest\AggregateA;
use TalisOrm\Schema\AggregateSchemaProviderTest\AggregateB;

final class AggregateSchemaProviderTest extends TestCase
{
    /**
     * @test
     */
    public function it_builds_up_a_schema_by_letting_the_aggregates_specify_their_own_tables(): void
    {
        $schemaProvider = new AggregateSchemaProvider(
            DriverManager::getConnection([
                'driver' => 'pdo_sqlite'
            ]),
            [
                AggregateA::class,
                AggregateB::class
            ]
        );

        $schema = $schemaProvider->createSchema();

        // Note: the SchemaManager prefixes table names with the database name
        self::assertEquals(['public.a', 'public.b'], $schema->getTableNames());
    }
}
