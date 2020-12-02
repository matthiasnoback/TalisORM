<?php

namespace TalisOrm;

use TalisOrm\DomainEvents\EventRecordingCapabilities;

/**
 * @see AggregateBehavior
 * @phpstan-template T
 */
interface Aggregate extends Entity
{
    /**
     * If your aggregate state has this column, it will be used for preventing concurrency issues using
     * optimistic concurrency locking.
     */
    const VERSION_COLUMN = 'aggregate_version';

    /**
     * Return all the child entities of this aggregate, grouped by their type. Sample implementation:
     *
     *     return [
     *         Line::class => $this->lines()
     *     ];
     *
     * @return array<string, mixed[]>
     *
     * @phpstan-return array<class-string<ChildEntity>, mixed[]>
     */
    public function childEntitiesByType(): array;

    /**
     * Return all child entity types for this aggregate. Sample implementation:
     *
     *     return [
     *         Line::class
     *     ]
     *
     * @return array<int, string>
     *
     * @phpstan-return array<int, class-string<ChildEntity>>
     */
    public static function childEntityTypes(): array;

    /**
     * Recreate the root entity, based on the state that was retrieved from the database. This can be expected to be
     * equivalent to the state that was earlier returned by `Aggregate::getState()`. Sample implementation:
     *
     * public static function fromState(array $aggregateState, array $childEntitiesByType): Aggregate
     * {
     *     list($orderState, $lineStates) = $states;
     *
     *     $order = new self();
     *     $order->orderId = new OrderId($aggregateState['order_id']);
     *     // ...
     *
     *     $order->lines = $childEntitiesByType[Line::class];
     *
     *     return $order;
     * }
     *
     * @param array<string, mixed> $aggregateState
     * @param array<string, mixed[]> $childEntitiesByType
     * @return static
     *
     * @phpstan-param array<class-string<ChildEntity>, mixed[]> $childEntitiesByType
     * @phpstan-return T
     */
    public static function fromState(array $aggregateState, array $childEntitiesByType);

    /**
     * Return any deleted child entities.
     *
     * @return ChildEntity[]
     */
    public function deletedChildEntities(): array;

    /**
     * Return domain events that have been recorded internally, and immediately forget about them. That is: a second
     * call to this method would return an empty array.
     *
     * @see EventRecordingCapabilities
     * @return object[]
     */
    public function releaseEvents(): array;
}
