<?php

namespace TalisOrm\Schema;

use Doctrine\DBAL\Schema\Schema;

interface SpecifiesSchema
{
    /**
     * @param Schema $schema
     * @return void
     */
    public static function specifySchema(Schema $schema);
}
