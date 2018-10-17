<?php

namespace TalisOrm\AggregateRepositoryTest\SimpleAggregate;

use Doctrine\DBAL\Schema\Schema;
use TalisOrm\Aggregate;
use TalisOrm\AggregateId;
use TalisOrm\DomainEvents\EventRecordingCapabilities;
use TalisOrm\Schema\SpecifiesSchema;
use Webmozart\Assert\Assert;

final class SimpleAggregate implements Aggregate, SpecifiesSchema
{
    use EventRecordingCapabilities;

    /**
     * @var SimpleAggregateId
     */
    private $aggregateId;

    public function __construct(SimpleAggregateId $aggregateId)
    {
        $this->aggregateId = $aggregateId;
    }

    public function childEntitiesByType()
    {
        return [];
    }

    public static function childEntityTypes()
    {
        return [];
    }

    public function state()
    {
        return [
            'aggregate_id' => $this->aggregateId->id()
        ];
    }

    public static function fromState(array $aggregateState, array $childEntitiesByType)
    {
        return new self(new SimpleAggregateId($aggregateState['aggregate_id']));
    }

    public static function tableName()
    {
        return 'simple_aggregate';
    }

    public function identifier()
    {
        return [
            'aggregate_id' => $this->aggregateId->id()
        ];
    }

    public static function identifierForQuery(AggregateId $aggregateId)
    {
        Assert::isInstanceOf($aggregateId, SimpleAggregateId::class);
        /** @var SimpleAggregateId $aggregateId */

        return [
            'aggregate_id' => $aggregateId->id()
        ];
    }

    public function deletedChildEntities()
    {
        return [];
    }

    public static function specifySchema(Schema $schema)
    {
        $table = $schema->createTable('simple_aggregate');
        $table->addColumn('aggregate_id', 'integer');
        $table->setPrimaryKey(['aggregate_id']);
    }
}
