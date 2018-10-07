<?php

namespace TalisOrm;

use Doctrine\DBAL\DriverManager;

final class AggregateRepositorySqliteTest extends AbstractAggregateRepositoryTest
{
    protected function setUpConnection()
    {
        return DriverManager::getConnection([
            'driver' => 'pdo_sqlite'
        ]);
    }
}
