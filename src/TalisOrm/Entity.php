<?php

namespace TalisOrm;

interface Entity
{
    /**
     * Return an array representing the state of this entity. The keys of this array are the exact names of the
     * database columns. Sample implementation:
     *
     *     return [
     *         'order_id' => 21,
     *         'order_date' => '2018-10-03'
     *     ];
     *
     * If your aggregate uses optimistic concurrency, make sure you increment the `aggregate_version` column every
     * time this method gets called.
     */
    public function state(): array;

    /**
     * Return the name of the table for this entity. Sample implementation:
     *
     *     return 'users';
     */
    public static function tableName(): string;

    /**
     * Return an array of columns and values which uniquely identify this entity. Sample implementation:
     *
     *     return [
     *         'order_id' => 21,
     *         'company_id' => 5
     *     ];
     */
    public function identifier(): array;

    /**
     * Return an array of columns and values that should be used to find the aggregate with the given ID. Sample
     * implementation:
     *
     *     return [
     *         'order_id' => $orderId->orderId(),
     *         'company_id' => $orderId->companyId()
     *     ];
     *
     */
    public static function identifierForQuery(AggregateId $aggregateId): array;

    /**
     * Return a boolean indicating whether or not this entity is new, i.e. requires an INSERT statement to be used
     * when saving it.
     */
    public function isNew(): bool;

    /**
     * Will be called to mark an entity as "persisted". From this moment on, `isNew()` should return `false`.
     */
    public function markAsPersisted(): void;
}
