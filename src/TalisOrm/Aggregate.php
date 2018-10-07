<?php

namespace TalisOrm;

use TalisOrm\DomainEvents\EventRecordingCapabilities;

interface Aggregate extends Entity
{
    /**
     * Return all the child entities of this aggregate, grouped by their type. Sample implementation:
     *
     *     return [
     *         Line::class => $this->lines()
     *     ];
     *
     * @return array
     */
    public function childEntitiesByType();

    /**
     * Return all child entity types for this aggregate. Sample implementation:
     *
     *     return [
     *         Line::class
     *     ]
     *
     * @return array
     */
    public static function childEntityTypes();

    /**
     * Recreate the root entity, based on the state that was retrieved from the database. This can be expected to be
     * equivalent to the state that was earlier returned by `Aggregate::getState()`. Sample implementation:
     *
     * public static function fromState(array $aggregateState, array $childEntityStatesByType): Aggregate
     * {
     *     list($orderState, $lineStates) = $states;
     *
     *     $order = new self();
     *     $order->orderId = new OrderId($aggregateState['order_id']);
     *     // ...
     *
     *     $order->lines = [];
     *     foreach ($childEntityStatesByType[Line::class] as $lineState) {
     *         $line = Line::fromState($lineState);
     *         $order->lines[] = $line;
     *     }
     * }
     *
     * @param array $aggregateState
     * @param array $childEntityStatesByType
     * @return static
     */
    public static function fromState(array $aggregateState, array $childEntityStatesByType);

    /**
     * Return any deleted child entities.
     *
     * @return ChildEntity[]
     */
    public function deletedChildEntities();

    /**
     * Return domain events that have been recorded internally, and immediately forget about them. That is: a second
     * call to this method would return an empty array.
     *
     * @see EventRecordingCapabilities
     * @return object[]
     */
    public function releaseEvents();
}
