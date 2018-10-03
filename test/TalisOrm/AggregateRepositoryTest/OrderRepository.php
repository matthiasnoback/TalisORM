<?php
declare(strict_types=1);

namespace TalisOrm\AggregateRepositoryTest;

interface OrderRepository
{
    public function save(Order $order): void;

    public function getById(OrderId $orderId): Order;

    public function delete(Order $order): void;
}
