<?php

namespace TalisOrm\AggregateRepositoryTest;

use Doctrine\DBAL\Schema\Schema;
use stdClass;
use TalisOrm\Aggregate;
use TalisOrm\AggregateId;
use TalisOrm\DomainEvents\EventRecordingCapabilities;
use TalisOrm\Schema\SpecifiesSchema;
use Webmozart\Assert\Assert;

final class FromStateDoesNotReturnAnAggregate implements Aggregate, SpecifiesSchema
{
    use EventRecordingCapabilities;

    /**
     * @var FromStateDoesNotReturnAnAggregateId
     */
    private $aggregateId;

    public function __construct(FromStateDoesNotReturnAnAggregateId $aggregateId)
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

    public static function fromState(array $aggregateState, array $childEntityStatesByType)
    {
        // Note: not an instance of this aggregate class
        return new stdClass();
    }

    public static function tableName()
    {
        return 'from_state_does_not_return_an_aggregate';
    }

    public function identifier()
    {
        return [
            'aggregate_id' => $this->aggregateId->id()
        ];
    }

    public static function identifierForQuery(AggregateId $aggregateId)
    {
        Assert::isInstanceOf($aggregateId, FromStateDoesNotReturnAnAggregateId::class);
        /** @var FromStateDoesNotReturnAnAggregateId $aggregateId */

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
        $table = $schema->createTable('from_state_does_not_return_an_aggregate');
        $table->addColumn('aggregate_id', 'integer');
        $table->addUniqueIndex(['aggregate_id']);
    }

    public function isNew()
    {
        return true;
    }
}
