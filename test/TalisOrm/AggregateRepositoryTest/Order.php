<?php
declare(strict_types=1);

namespace TalisOrm\AggregateRepositoryTest;

use DateTimeImmutable;
use TalisOrm\Aggregate;
use Webmozart\Assert\Assert;

final class Order implements Aggregate
{
    /**
     * @var OrderId
     */
    private $orderId;

    /**
     * @var DateTimeImmutable
     */
    private $orderDate;

    /**
     * @var Line[]
     */
    private $lines = [];

    private function __construct()
    {
    }

    public static function create(OrderId $orderId, DateTimeImmutable $orderDate): Order
    {
        $order = new self();

        $order->orderId = $orderId;
        $order->orderDate = $orderDate;

        return $order;
    }

    public function addLine(LineNumber $lineId, ProductId $productId, Quantity $quantity): void
    {
        $this->lines[] = Line::create($this->orderId, $lineId, $productId, $quantity);
    }

    public function orderId(): OrderId
    {
        return $this->orderId;
    }

    public function childEntitiesByType(): array
    {
        return [
            Line::class => $this->lines
        ];
    }

    public static function childEntityTypes(): array
    {
        return [
            Line::class
        ];
    }

    public function state(): array
    {
        return [
            'order_id' => $this->orderId->orderId(),
            'company_id' => $this->orderId->companyId(),
            'order_date' => $this->orderDate->format('Y-m-d')
        ];
    }

    public static function fromState(array ...$states): Aggregate
    {
        list($orderState, $lineStates) = $states;
        $order = new self();

        $order->orderId = new OrderId($orderState['order_id'], (int)$orderState['company_id']);
        $order->orderDate = DateTimeImmutable::createFromFormat('Y-m-d', $orderState['order_date']);

        $order->lines = [];
        foreach ($lineStates as $lineState) {
            $order->lines[] = Line::fromState($lineState);
        }

        return $order;
    }

    public static function tableName(): string
    {
        return 'orders';
    }

    public function identifier(): array
    {
        return [
            'order_id' => $this->orderId->orderId(),
            'company_id' => $this->orderId->companyId()
        ];
    }

    public static function identifierForQuery($id): array
    {
        Assert::isInstanceOf($id, OrderId::class);

        return [
            'order_id' => $id->orderId(),
            'company_id' => $id->companyId()
        ];
    }
}
