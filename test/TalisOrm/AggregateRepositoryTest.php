<?php

namespace TalisOrm;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Synchronizer\SingleDatabaseSynchronizer;
use PHPUnit\Framework\TestCase;
use TalisOrm\AggregateRepositoryTest\EventDispatcherSpy;
use TalisOrm\AggregateRepositoryTest\LineAdded;
use TalisOrm\AggregateRepositoryTest\LineDeleted;
use TalisOrm\AggregateRepositoryTest\LineNumber;
use TalisOrm\AggregateRepositoryTest\LineUpdated;
use TalisOrm\AggregateRepositoryTest\Order;
use TalisOrm\AggregateRepositoryTest\OrderCreated;
use TalisOrm\AggregateRepositoryTest\OrderId;
use TalisOrm\AggregateRepositoryTest\OrderTalisOrmRepository;
use TalisOrm\AggregateRepositoryTest\OrderUpdated;
use TalisOrm\AggregateRepositoryTest\ProductId;
use TalisOrm\AggregateRepositoryTest\Quantity;
use TalisOrm\Schema\AggregateSchemaProvider;
use Webmozart\Assert\Assert;

final class AggregateRepositoryTest extends TestCase
{
    /**
     * @var OrderTalisOrmRepository
     */
    private $repository;

    /**
     * @var EventDispatcherSpy
     */
    private $eventDispatcher;

    /**
     * @var Connection
     */
    private $connection;

    protected function setUp()
    {
        $this->connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite'
        ]);

        $schemaProvider = new AggregateSchemaProvider($this->connection, [
            Order::class
        ]);
        $synchronizer = new SingleDatabaseSynchronizer($this->connection);
        $synchronizer->createSchema($schemaProvider->createSchema());

        $this->eventDispatcher = new EventDispatcherSpy();
        $this->repository = new OrderTalisOrmRepository(
            new AggregateRepository($this->connection, $this->eventDispatcher)
        );
    }

    protected function tearDown()
    {
        $this->connection->close();
    }

    /**
     * @test
     */
    public function it_saves_an_aggregate()
    {
        $aggregate = Order::create(
            new OrderId('91338a57-5c9a-40e8-b5e8-803e8175c7d7', 5),
            self::createDateTimeImmutable('2018-10-03')
        );
        $this->repository->save($aggregate);

        $fromDatabase = $this->repository->getById($aggregate->orderId());

        self::assertEquals($aggregate, $fromDatabase);
        self::assertEquals([new OrderCreated()], $this->eventDispatcher->dispatchedEvents());
    }

    /**
     * @test
     */
    public function it_updates_an_aggregate()
    {
        $aggregate = Order::create(
            new OrderId('91338a57-5c9a-40e8-b5e8-803e8175c7d7', 5),
            self::createDateTimeImmutable('2018-10-03')
        );
        $this->repository->save($aggregate);

        $aggregate->update(self::createDateTimeImmutable('2018-11-05'));
        $this->repository->save($aggregate);

        $fromDatabase = $this->repository->getById($aggregate->orderId());

        self::assertEquals($aggregate, $fromDatabase);
        self::assertEquals(
            [
                new OrderCreated(),
                new OrderUpdated()
            ],
            $this->eventDispatcher->dispatchedEvents()
        );
    }

    /**
     * @test
     */
    public function it_guards_against_concurrent_updates()
    {
        $aggregate = Order::create(
            new OrderId('91338a57-5c9a-40e8-b5e8-803e8175c7d7', 5),
            self::createDateTimeImmutable('2018-10-03')
        );
        $this->repository->save($aggregate);

        // we fetch the aggregate in two different places
        $aggregate1 = $this->repository->getById($aggregate->orderId());
        $aggregate2 = $this->repository->getById($aggregate->orderId());

        // we update the first aggregate
        $aggregate1->update(self::createDateTimeImmutable('2018-11-05'));
        $this->repository->save($aggregate1);

        $this->expectException(ConcurrentUpdate::class);
        /*
         * Now we save the other aggregate. This results in a concurrency issue, since it has the same version as the
         * first aggregate which we just saved to the database.
         */
        $this->repository->save($aggregate2);
    }

    /**
     * @test
     */
    public function it_guards_against_concurrent_updates_if_the_aggregate_has_a_lower_version()
    {
        $aggregate = Order::create(
            new OrderId('91338a57-5c9a-40e8-b5e8-803e8175c7d7', 5),
            self::createDateTimeImmutable('2018-10-03')
        );
        $this->repository->save($aggregate);

        // we fetch the aggregate in two different places
        $aggregate1 = $this->repository->getById($aggregate->orderId());
        $aggregate2 = $this->repository->getById($aggregate->orderId());

        // we update the first aggregate
        $aggregate1->update(self::createDateTimeImmutable('2018-11-05'));
        $this->repository->save($aggregate1);

        $this->expectException(ConcurrentUpdate::class);
        /*
         * Now we save the other aggregate. This results in a concurrency issue, since it has a lower version than the
         * first aggregate which we just saved to the database.
         */
        $aggregate2->setAggregateVersion(0);
        $this->repository->save($aggregate2);
    }

    /**
     * @test
     */
    public function it_saves_an_aggregate_with_its_child_entities()
    {
        $aggregate = Order::create(
            new OrderId('91338a57-5c9a-40e8-b5e8-803e8175c7d7', 5),
            self::createDateTimeImmutable('2018-10-03')
        );
        $aggregate->addLine(
            new LineNumber(1),
            new ProductId('73d46c97-a71b-4e3c-9633-bb7a8603b301', 5),
            new Quantity(10)
        );
        $this->repository->save($aggregate);

        $fromDatabase = $this->repository->getById($aggregate->orderId());

        self::assertEquals($aggregate, $fromDatabase);
        self::assertEquals(
            [
                new OrderCreated(),
                new LineAdded()
            ],
            $this->eventDispatcher->dispatchedEvents()
        );
    }

    /**
     * @test
     */
    public function it_creates_multiple_child_entities_in_the_database()
    {
        $aggregate = Order::create(
            new OrderId('91338a57-5c9a-40e8-b5e8-803e8175c7d7', 5),
            self::createDateTimeImmutable('2018-10-03')
        );
        $aggregate->addLine(
            new LineNumber(1),
            new ProductId('73d46c97-a71b-4e3c-9633-bb7a8603b301', 5),
            new Quantity(10)
        );
        $aggregate->addLine(
            new LineNumber(2),
            new ProductId('4a1828d4-f87d-4d6e-9fc7-ce2ccbc23247', 5),
            new Quantity(5)
        );
        $this->repository->save($aggregate);

        $fromDatabase = $this->repository->getById($aggregate->orderId());

        self::assertEquals($aggregate, $fromDatabase);
        self::assertEquals(
            [
                new OrderCreated(),
                new LineAdded(),
                new LineAdded()
            ],
            $this->eventDispatcher->dispatchedEvents()
        );
    }

    /**
     * @test
     */
    public function it_updates_multiple_child_entities_in_the_database()
    {
        $aggregate = Order::create(
            new OrderId('91338a57-5c9a-40e8-b5e8-803e8175c7d7', 5),
            self::createDateTimeImmutable('2018-10-03')
        );
        $aggregate->addLine(
            new LineNumber(1),
            new ProductId('73d46c97-a71b-4e3c-9633-bb7a8603b301', 5),
            new Quantity(10)
        );
        $aggregate->addLine(
            new LineNumber(2),
            new ProductId('4a1828d4-f87d-4d6e-9fc7-ce2ccbc23247', 5),
            new Quantity(5)
        );
        $this->repository->save($aggregate);

        $aggregate->updateLine(
            new LineNumber(2),
            new ProductId('ec739f60-0d09-47f5-ae42-e2157ba709e2', 5),
            new Quantity(7)
        );
        $this->repository->save($aggregate);

        $fromDatabase = $this->repository->getById($aggregate->orderId());

        self::assertEquals($aggregate, $fromDatabase);
        self::assertEquals(
            [
                new OrderCreated(),
                new LineAdded(),
                new LineAdded(),
                new LineUpdated(),
            ],
            $this->eventDispatcher->dispatchedEvents()
        );
    }

    /**
     * @test
     */
    public function it_deletes_child_entities_that_have_been_removed_from_the_aggregate()
    {
        $aggregate = Order::create(
            new OrderId('91338a57-5c9a-40e8-b5e8-803e8175c7d7', 5),
            self::createDateTimeImmutable('2018-10-03')
        );
        $aggregate->addLine(
            new LineNumber(1),
            new ProductId('73d46c97-a71b-4e3c-9633-bb7a8603b301', 5),
            new Quantity(10)
        );
        $aggregate->addLine(
            new LineNumber(2),
            new ProductId('4a1828d4-f87d-4d6e-9fc7-ce2ccbc23247', 5),
            new Quantity(5)
        );
        $this->repository->save($aggregate);

        $aggregate->deleteLine(new LineNumber(2));
        $this->repository->save($aggregate);

        $fromDatabase = $this->repository->getById($aggregate->orderId());

        self::assertEquals($aggregate, $fromDatabase);
        self::assertEquals(
            [
                new OrderCreated(),
                new LineAdded(),
                new LineAdded(),
                new LineDeleted(),
            ],
            $this->eventDispatcher->dispatchedEvents()
        );
    }

    /**
     * @test
     */
    public function it_deletes_an_aggregate_with_its_child_entities()
    {
        $aggregate = Order::create(
            new OrderId('91338a57-5c9a-40e8-b5e8-803e8175c7d7', 5),
            self::createDateTimeImmutable('2018-10-03')
        );
        $aggregate->addLine(
            new LineNumber(1),
            new ProductId('73d46c97-a71b-4e3c-9633-bb7a8603b301', 5),
            new Quantity(10)
        );
        $this->repository->save($aggregate);

        $this->repository->delete($aggregate);

        $this->expectException(AggregateNotFoundException::class);
        $this->repository->getById($aggregate->orderId());
    }

    /**
     * @test
     */
    public function it_does_not_delete_all_aggregates_of_the_same_type()
    {
        $aggregate1 = Order::create(
            new OrderId('91338a57-5c9a-40e8-b5e8-803e8175c7d7', 5),
            self::createDateTimeImmutable('2018-10-03')
        );
        $this->repository->save($aggregate1);
        $aggregate2 = Order::create(
            new OrderId('c8ee1ee6-7757-4661-81fb-5b327badbff8', 5),
            self::createDateTimeImmutable('2018-10-04')
        );
        $this->repository->save($aggregate2);

        $this->repository->delete($aggregate1);

        // aggregate2 should not be touched
        self::assertEquals($aggregate2, $this->repository->getById($aggregate2->orderId()));

        // aggregate1 can't be found
        $this->expectException(AggregateNotFoundException::class);
        $this->repository->getById($aggregate1->orderId());
    }

    /**
     * @param string $date
     * @return DateTimeImmutable
     */
    private static function createDateTimeImmutable($date)
    {
        Assert::string($date);

        $dateTimeImmutable = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        Assert::isInstanceOf($dateTimeImmutable, DateTimeImmutable::class);

        return $dateTimeImmutable;
    }
}
