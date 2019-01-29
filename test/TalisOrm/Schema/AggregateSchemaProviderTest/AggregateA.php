<?php

namespace TalisOrm\Schema\AggregateSchemaProviderTest;

use Doctrine\DBAL\Schema\Schema;
use TalisOrm\Schema\SpecifiesSchema;

final class AggregateA implements SpecifiesSchema
{
    public static function specifySchema(Schema $schema): void
    {
        $table = $schema->createTable('a');
        $table->addColumn('id', 'string');
        $table->addUniqueIndex(['id']);
    }
}
