<?php

namespace TalisOrm\Schema;

use Doctrine\DBAL\Schema\Schema;

interface SpecifiesSchema
{
    public static function specifySchema(Schema $schema): void;
}
