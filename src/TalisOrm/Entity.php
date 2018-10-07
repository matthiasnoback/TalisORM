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
     * @return array
     */
    public function state();

    /**
     * Return the name of the table for this entity. Sample implementation:
     *
     *     return 'users';
     *
     * @return string
     */
    public static function tableName();

    /**
     * Return an array of columns and values which uniquely identify this entity. Sample implementation:
     *
     *     return [
     *         'order_id' => 21,
     *         'company_id' => 5
     *     ];
     *
     * @return array
     */
    public function identifier();

    /**
     * Return an array of columns and values that should be used to find the aggregate with the given ID. Sample
     * implementation:
     *
     *     return [
     *         'order_id' => $orderId->orderId(),
     *         'company_id' => $orderId->companyId()
     *     ];
     *
     * @param AggregateId $aggregateId
     * @return array
     */
    public static function identifierForQuery(AggregateId $aggregateId);
}
