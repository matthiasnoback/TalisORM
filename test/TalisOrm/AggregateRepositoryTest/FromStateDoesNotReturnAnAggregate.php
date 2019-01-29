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

    public function childEntitiesByType(): array
    {
        return [];
    }

    public static function childEntityTypes(): array
    {
        return [];
    }

    public function state(): array
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

    public static function tableName(): string
    {
        return 'from_state_does_not_return_an_aggregate';
    }

    public function identifier(): array
    {
        return [
            'aggregate_id' => $this->aggregateId->id()
        ];
    }

    public static function identifierForQuery(AggregateId $aggregateId): array
    {
        Assert::isInstanceOf($aggregateId, FromStateDoesNotReturnAnAggregateId::class);
        /** @var FromStateDoesNotReturnAnAggregateId $aggregateId */

        return [
            'aggregate_id' => $aggregateId->id()
        ];
    }

    public function deletedChildEntities(): array
    {
        return [];
    }

    public static function specifySchema(Schema $schema): void
    {
        $table = $schema->createTable('from_state_does_not_return_an_aggregate');
        $table->addColumn('aggregate_id', 'integer');
        $table->addUniqueIndex(['aggregate_id']);
    }

    public function isNew(): bool
    {
        return true;
    }

    public function markAsPersisted(): void
    {
        // do nothing
    }
}
