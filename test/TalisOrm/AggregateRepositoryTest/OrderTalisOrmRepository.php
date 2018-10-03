<?php
declare(strict_types=1);

namespace TalisOrm\AggregateRepositoryTest;

use TalisOrm\AggregateRepository;

/**
 * This is your implementation of the OrderRepository interface from your _Domain_ layer. This implementation itself
 * belongs to the _Infrastructure_ layer.
 */
final class OrderTalisOrmRepository implements OrderRepository
{
    /**
     * @var AggregateRepository
     */
    private $aggregateRepository;

    public function __construct(AggregateRepository $aggregateRepository)
    {
        $this->aggregateRepository = $aggregateRepository;
    }

    public function save(Order $order): void
    {
        $this->aggregateRepository->save($order);

        // you may dispatch events at this point
    }

    public function getById(OrderId $orderId): Order
    {
        return $this->aggregateRepository->getById(Order::class, $orderId);
    }

    public function delete(Order $order): void
    {
        $this->aggregateRepository->delete($order);
    }
}
