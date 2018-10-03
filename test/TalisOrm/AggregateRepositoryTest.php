<?php
declare(strict_types=1);

namespace TalisOrm;

use DateTimeImmutable;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Synchronizer\SingleDatabaseSynchronizer;
use PHPUnit\Framework\TestCase;
use TalisOrm\AggregateRepositoryTest\LineNumber;
use TalisOrm\AggregateRepositoryTest\Order;
use TalisOrm\AggregateRepositoryTest\OrderId;
use TalisOrm\AggregateRepositoryTest\ProductId;
use TalisOrm\AggregateRepositoryTest\Quantity;
use Webmozart\Assert\Assert;

final class AggregateRepositoryTest extends TestCase
{
    /**
     * @var AggregateRepository
     */
    private $repository;

    /**
     * @var Connection
     */
    private $connection;

    protected function setUp()
    {
        $config = new Configuration();
        $this->connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite'
        ], $config);

        $schema = $this->connection->getSchemaManager()->createSchema();
        $orderTable = $schema->createTable('orders');
        $orderTable->addColumn('order_id', 'string');
        $orderTable->addColumn('company_id', 'integer');
        $orderTable->addColumn('order_date', 'date');
        $orderTable->addUniqueIndex(['order_id', 'company_id']);

        $linesTable = $schema->createTable('lines');
        $linesTable->addColumn('order_id', 'string');
        $linesTable->addColumn('company_id', 'integer');
        $linesTable->addColumn('line_number', 'integer');
        $linesTable->addColumn('product_id', 'string');
        $linesTable->addColumn('quantity', 'integer');
        $linesTable->addUniqueIndex(['order_id', 'company_id', 'line_number']);

        $synchronizer = new SingleDatabaseSynchronizer($this->connection);
        $synchronizer->createSchema($schema);

        $this->repository = new AggregateRepository($this->connection);
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

        $fromDatabase = $this->repository->getById(Order::class, $aggregate->orderId());

        self::assertEquals($aggregate, $fromDatabase);
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

        $fromDatabase = $this->repository->getById(Order::class, $aggregate->orderId());

        self::assertEquals($aggregate, $fromDatabase);
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

        $fromDatabase = $this->repository->getById(Order::class, $aggregate->orderId());

        self::assertEquals($aggregate, $fromDatabase);
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

        $fromDatabase = $this->repository->getById(Order::class, $aggregate->orderId());

        self::assertEquals($aggregate, $fromDatabase);
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

        $fromDatabase = $this->repository->getById(Order::class, $aggregate->orderId());

        self::assertEquals($aggregate, $fromDatabase);
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

        $fromDatabase = $this->repository->getById(Order::class, $aggregate->orderId());

        self::assertEquals($aggregate, $fromDatabase);
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
        $this->repository->getById(Order::class, $aggregate->orderId());
    }

    private static function createDateTimeImmutable($date): DateTimeImmutable
    {
        $dateTimeImmutable = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        Assert::isInstanceOf($dateTimeImmutable, DateTimeImmutable::class);

        return $dateTimeImmutable;
    }
}
