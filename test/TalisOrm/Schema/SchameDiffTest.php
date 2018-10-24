<?php

namespace TalisOrm\Schema;

use PHPUnit\Framework\TestCase;
use TalisOrm\Schema\AggregateSchemaProviderTest\AggregateA;

class SchemaDiffTest extends TestCase
{

    public funtion testMustReturnJustNewChangesToSchame()
    {
        $schemaProvider = new AggregateSchemaProvider([
            AggregateA::class,
        ]);

        $domainSchema = $schemaProvider->createSchema();
        $currentSchema = new Schema();
        $currentSchema->createTable('a');
    }
}