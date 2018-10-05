<?php
declare(strict_types=1);

namespace TalisOrm\AggregateRepositoryTest;

use DateTimeImmutable;
use Doctrine\DBAL\Schema\Schema;
use TalisOrm\Aggregate;
use TalisOrm\AggregateId;
use TalisOrm\ChildEntity;
use TalisOrm\DomainEvents\EventRecordingCapabilities;
use TalisOrm\DomainEvents\RecordsDomainEvents;
use TalisOrm\Schema\SpecifiesSchema;
use Webmozart\Assert\Assert;

final class Order implements Aggregate, SpecifiesSchema
{
    use EventRecordingCapabilities;

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

    /**
     * @var array
     */
    private $deletedChildEntities = [];

    private function __construct()
    {
    }

    public static function create(OrderId $orderId, DateTimeImmutable $orderDate): Order
    {
        $order = new self();

        $order->orderId = $orderId;
        $order->orderDate = $orderDate;

        $order->recordThat(new OrderCreated());

        return $order;
    }

    public function update(DateTimeImmutable $orderDate): void
    {
        $this->orderDate = $orderDate;

        $this->recordThat(new OrderUpdated());
    }

    public function addLine(LineNumber $lineId, ProductId $productId, Quantity $quantity): void
    {
        $this->lines[] = Line::create($this->orderId, $lineId, $productId, $quantity);

        $this->recordThat(new LineAdded());
    }

    public function updateLine(LineNumber $lineId, ProductId $productId, Quantity $quantity): void
    {
        foreach ($this->lines as $index => $line) {
            if ($line->lineNumber()->asInt() === $lineId->asInt()) {
                $line->update($productId, $quantity);
            }
        }

        $this->recordThat(new LineUpdated());
    }

    public function deleteLine(LineNumber $lineId): void
    {
        foreach ($this->lines as $index => $line) {
            if ($line->lineNumber()->asInt() === $lineId->asInt()) {
                unset($this->lines[$index]);
                $this->deleteChildEntity($line);
            }
        }

        $this->recordThat(new LineDeleted());
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
        $dateTimeImmutable = DateTimeImmutable::createFromFormat('Y-m-d', $orderState['order_date']);

        if (!$dateTimeImmutable instanceof DateTimeImmutable) {
            throw new \RuntimeException('Invalid date string from database');
        }
        $order->orderDate = $dateTimeImmutable;

        $order->lines = [];
        foreach ($lineStates as $lineState) {
            $entity = Line::fromState($lineState);
            Assert::isInstanceOf($entity, Line::class);
            $order->lines[] = $entity;
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

    public static function identifierForQuery(AggregateId $aggregateId): array
    {
        Assert::isInstanceOf($aggregateId, OrderId::class);
        /** @var OrderId $aggregateId */

        return [
            'order_id' => $aggregateId->orderId(),
            'company_id' => $aggregateId->companyId()
        ];
    }

    public function deletedChildEntities(): array
    {
        $deletedChildEntities = $this->deletedChildEntities;

        $this->deletedChildEntities = [];

        return $deletedChildEntities;
    }

    private function deleteChildEntity(ChildEntity $childEntity): void
    {
        $this->deletedChildEntities[] = $childEntity;
    }

    public static function specifySchema(Schema $schema): void
    {
        $table = $schema->createTable('orders');
        $table->addColumn('order_id', 'string');
        $table->addColumn('company_id', 'integer');
        $table->addColumn('order_date', 'date');
        $table->addUniqueIndex(['order_id', 'company_id']);

        Line::specifySchema($schema);
    }
}
