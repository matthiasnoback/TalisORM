<?php
declare(strict_types=1);

namespace TalisOrm;

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
    public function childEntitiesByType(): array;

    /**
     * Return all child entity types for this aggregate. Sample implementation:
     *
     *     return [
     *         Line::class
     *     ]
     *
     * @return array
     */
    public static function childEntityTypes(): array;

    /**
     * Recreate the root entity, based on the state that was retrieved from the database. This can be expected to be
     * equivalent to the state that was earlier returned by `Aggregate::getState()`. Sample implementation:
     *
     * public static function fromState(array ...$states): Aggregate
     * {
     *     list($orderState, $lineStates) = $states;
     *
     *     $order = new self();
     *     // ...
     *
     *     $order->lines = [];
     *     foreach ($lineStates as $lineState) {
     *         $line = Line::fromState($lineState);
     *         $order->lines[] = $line;
     *     }
     * }
     *
     * @param array[] ...$states
     * @return static
     */
    public static function fromState(array ...$states): Aggregate;
}
