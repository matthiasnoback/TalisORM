<?php
declare(strict_types=1);

namespace TalisOrm\Schema;

use Doctrine\DBAL\Schema\Schema;

interface SpecifiesSchema
{
    /**
     * @param Schema $schema
     */
    public static function specifySchema(Schema $schema): void;
}
