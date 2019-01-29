<?php

namespace TalisOrm;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Synchronizer\SingleDatabaseSynchronizer;
use PHPUnit\Framework\TestCase;
use TalisOrm\AggregateRepositoryTest\AggregateIdDummy;
use TalisOrm\AggregateRepositoryTest\EventDispatcherSpy;
use TalisOrm\AggregateRepositoryTest\FromStateDoesNotReturnAnAggregate;
use TalisOrm\AggregateRepositoryTest\FromStateDoesNotReturnAnAggregateId;
use TalisOrm\AggregateRepositoryTest\NotAnAggregateClass;
use TalisOrm\Schema\AggregateSchemaProvider;

final class ExceptionalSituationsTest extends TestCase
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var AggregateRepository
     */
    private $repository;

    protected function setUp()
    {
        $this->connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite'
        ]);

        $schemaProvider = new AggregateSchemaProvider([
            FromStateDoesNotReturnAnAggregate::class
        ]);
        $synchronizer = new SingleDatabaseSynchronizer($this->connection);
        $synchronizer->dropAllSchema();
        $synchronizer->createSchema($schemaProvider->createSchema());

        $this->repository = new AggregateRepository($this->connection, new EventDispatcherSpy());
    }

    protected function tearDown()
    {
        $this->connection->close();
    }

    /**
     * @test
     */
    public function the_aggregate_repository_requires_fromState_to_return_an_aggregate_instance()
    {
        $this->repository->save(
            new FromStateDoesNotReturnAnAggregate(
                new FromStateDoesNotReturnAnAggregateId(1)
            )
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('fromState');

        $this->repository->getById(
            FromStateDoesNotReturnAnAggregate::class,
            new FromStateDoesNotReturnAnAggregateId(1)
        );
    }

    /**
     * @test
     */
    public function the_aggregate_repository_requires_an_actual_aggregate_class_to_be_provided()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(Aggregate::class);

        $this->repository->getById(NotAnAggregateClass::class, new AggregateIdDummy());
    }
}
