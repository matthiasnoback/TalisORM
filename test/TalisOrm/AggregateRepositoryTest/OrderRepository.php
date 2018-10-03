<?php
declare(strict_types=1);

namespace TalisOrm\AggregateRepositoryTest;

/**
 * This is your own repository interface, which belongs to your application's _Domain_ layer.
 */
interface OrderRepository
{
    public function save(Order $order): void;

    public function getById(OrderId $orderId): Order;

    public function delete(Order $order): void;
}
