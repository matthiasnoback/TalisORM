<?php

namespace TalisOrm;

use Doctrine\DBAL\DriverManager;

final class AggregateRepositoryMySQLTest extends AbstractAggregateRepositoryTest
{
    protected function setUpConnection()
    {
        return DriverManager::getConnection([
            'url' => 'mysql://root:@mysql/default'
        ]);
    }
}
