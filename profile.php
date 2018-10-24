<?php

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Synchronizer\SingleDatabaseSynchronizer;
use TalisOrm\AggregateRepository;
use TalisOrm\AggregateRepositoryTest\EventDispatcherSpy;
use TalisOrm\AggregateRepositoryTest\LineNumber;
use TalisOrm\AggregateRepositoryTest\Order;
use TalisOrm\AggregateRepositoryTest\OrderId;
use TalisOrm\AggregateRepositoryTest\OrderTalisOrmRepository;
use TalisOrm\AggregateRepositoryTest\ProductId;
use TalisOrm\AggregateRepositoryTest\Quantity;
use TalisOrm\DateTimeUtil;
use TalisOrm\Schema\AggregateSchemaProvider;

require __DIR__ . '/vendor/autoload.php';

$connection = DriverManager::getConnection([
    'driver' => 'pdo_sqlite'
]);

$schemaProvider = new AggregateSchemaProvider([
    Order::class,
]);
$synchronizer = new SingleDatabaseSynchronizer($connection);
$synchronizer->createSchema($schemaProvider->createSchema());

$eventDispatcher = new EventDispatcherSpy();
$repository = new OrderTalisOrmRepository(
    new AggregateRepository($connection, $eventDispatcher)
);
$aggregate = Order::create(
    new OrderId('91338a57-5c9a-40e8-b5e8-803e8175c7d7', 5),
    DateTimeUtil::createDateTimeImmutable('2018-10-03')
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
$repository->save($aggregate);

$aggregate->deleteLine(new LineNumber(2));
$repository->save($aggregate);

$fromDatabase = $repository->getById($aggregate->orderId());
