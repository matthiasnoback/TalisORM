<?php

namespace TalisOrm\Schema\AggregateSchemaProviderTest;

use Doctrine\DBAL\Schema\Schema;
use TalisOrm\Schema\SpecifiesSchema;

final class AggregateB implements SpecifiesSchema
{
    public static function specifySchema(Schema $schema)
    {
        $table = $schema->createTable('b');
        $table->addColumn('id', 'string');
        $table->addUniqueIndex(['id']);
    }
}
