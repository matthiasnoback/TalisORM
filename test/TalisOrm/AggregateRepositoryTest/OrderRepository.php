<?php

namespace TalisOrm\AggregateRepositoryTest;

/**
 * This is your own repository interface, which belongs to your application's _Domain_ layer.
 */
interface OrderRepository
{
    /**
     * @param Order $order
     * @return void
     */
    public function save(Order $order);

    /**
     * @param OrderId $orderId
     * @return Order
     */
    public function getById(OrderId $orderId);

    /**
     * @param Order $order
     * @return void
     */
    public function delete(Order $order);
}
