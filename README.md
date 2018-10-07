[![Build Status](https://travis-ci.org/matthiasnoback/TalisORM.svg?branch=master)](https://travis-ci.org/matthiasnoback/TalisORM)

# About TalisORM

A good design starts with some limitations. You can start simple and keep building until you have a large ORM like Doctrine. Or you can choose not to support a mapping configuration, table inheritance, combined write/read models, navigable object graphs, lazy-loading, etc. That's what I'm looking for with TalisOrm. The rules are:

- You model a persistable domain object as an _Aggregate_: one (root) _Entity_, and optionally some _Child entities_.
- The child entities themselves have no children.
- You use the ORM for your _write model_ only. That is, you don't need to fetch hundreds of these aggregates to show them to the user.
- Your aggregate internally records domain events, which will automatically be released and dispatched after saving changes to the aggregate.

Furthermore:

- You're going to write your own mapping code, which converts your values or _Value objects_ to and from column values.

I explain more about the motivation for doing this in ["ORMless; a Memento-like pattern for object persistence"](https://matthiasnoback.nl/2018/03/ormless-a-memento-like-pattern-for-object-persistence).

You can find some examples of how to use this library in [test/TalisOrm/AggregateRepositoryTest/](test/TalisOrm/AggregateRepositoryTest/).

## Recording and dispatching domain events

A domain event is a simple object indicating that something has happened inside an aggregate (usually this just means that something has changed). You can use the [`EventRecordingCapabilities`](src/TalisOrm/DomainEvents/EventRecordingCapabilities.php) trait to save yourself from rewriting a couple of simple lines over and over again.

Immediately after saving an aggregate, the [`AggregateRepository`](src/TalisOrm/AggregateRepository.php) will call the aggregate's `releaseEvents()` method, which returns previously recorded domain events. It dispatches these events to an object that implements [`EventDispatcher`](src/TalisOrm/DomainEvents/EventDispatcher.php). As a user of this library you have to provide your own implementation of this interface, which is very simple. Maybe you just want to forward the call to your favorite event dispatcher, or the one that ships with your framework.

## Managing the database schema

Aggregates can implement `SpecifiesSchema` and, yes, specify their own schema. This can be useful if you want to use a tool to synchronize your current database schema with the schema that your aggregates expect, e.g. the Doctrine DBAL's own `SingleDatabaseSynchronizer`:

```php
use Doctrine\DBAL\Schema\Synchronizer\SingleDatabaseSynchronizer;
use TalisOrm\Schema\AggregateSchemaProvider

// set up or reuse a Doctrine\DBAL\Connection instance
$connection = ...;

$schemaProvider = new AggregateSchemaProvider($connection, [
    // list all the aggregate class names of your application, e.g.
    User::class,
    Order::class
]);
$synchronizer = new SingleDatabaseSynchronizer($connection);
$synchronizer->createSchema($schemaProvider->createSchema());
```

You could also use [Doctrine Migrations](https://github.com/doctrine/migrations/) to automatically generate migrations based on schema changes. It may need a bit of setup, but once you have it working, you'll notice that this tool needs a `SchemaProviderInterface` instance. You can easily set up an adapter for `AggregateSchemaProvider`. For example:

```php
final class AggregateMigrationsSchemaProvider implements SchemaProviderInterface
{
    /**
     * @var AggregateSchemaProvider
     */
    private $aggregateSchemaProvider;

    public function __construct(AggregateSchemaProvider $aggregateSchemaProvider)
    {
        $this->aggregateSchemaProvider = $aggregateSchemaProvider;
    }

    public function createSchema(): Schema
    {
        return $this->aggregateSchemaProvider->createSchema();
    }
}
```

## Supported PHP versions

Though I think everybody should be on the latest PHP version, I know that many of us aren't. I've actually written this library to be useful for a project I'm working on right now, which is still on PHP 5.6. So... For now, this library will support PHP 5.6 and up.
