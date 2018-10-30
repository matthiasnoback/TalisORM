<?php

namespace TalisOrm\AggregateRepositoryTest;

use DateTimeImmutable;
use Doctrine\DBAL\Schema\Schema;
use TalisOrm\Aggregate;
use TalisOrm\AggregateId;
use TalisOrm\ChildEntity;
use TalisOrm\DateTimeUtil;
use TalisOrm\DomainEvents\EventRecordingCapabilities;
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

    /**
     * @var bool
     */
    private $isNew = true;

    /**
     * @var int
     */
    private $aggregateVersion;

    private function __construct()
    {
    }

    /**
     * @param OrderId $orderId
     * @param DateTimeImmutable $orderDate
     * @return Order
     */
    public static function create(OrderId $orderId, DateTimeImmutable $orderDate)
    {
        $order = new self();

        $order->orderId = $orderId;
        $order->orderDate = $orderDate;

        $order->recordThat(new OrderCreated());

        return $order;
    }

    /**
     * @param DateTimeImmutable $orderDate
     * @return void
     */
    public function update(DateTimeImmutable $orderDate)
    {
        $this->orderDate = $orderDate;

        $this->recordThat(new OrderUpdated());
    }

    /**
     * @param LineNumber $lineId
     * @param ProductId $productId
     * @param Quantity $quantity
     * @return void
     */
    public function addLine(LineNumber $lineId, ProductId $productId, Quantity $quantity)
    {
        $this->lines[] = Line::create($this->orderId, $lineId, $productId, $quantity);

        $this->recordThat(new LineAdded());
    }

    /**
     * @param LineNumber $lineId
     * @param ProductId $productId
     * @param Quantity $quantity
     * @return void
     */
    public function updateLine(LineNumber $lineId, ProductId $productId, Quantity $quantity)
    {
        foreach ($this->lines as $index => $line) {
            if ($line->lineNumber()->asInt() === $lineId->asInt()) {
                $line->update($productId, $quantity);
            }
        }

        $this->recordThat(new LineUpdated());
    }

    /**
     * @param LineNumber $lineId
     * @return void
     */
    public function deleteLine(LineNumber $lineId)
    {
        foreach ($this->lines as $index => $line) {
            if ($line->lineNumber()->asInt() === $lineId->asInt()) {
                unset($this->lines[$index]);
                $this->deleteChildEntity($line);
            }
        }

        $this->recordThat(new LineDeleted());
    }

    /**
     * @return OrderId
     */
    public function orderId()
    {
        return $this->orderId;
    }

    public function childEntitiesByType()
    {
        return [
            Line::class => $this->lines
        ];
    }

    public static function childEntityTypes()
    {
        return [
            Line::class
        ];
    }

    public function state()
    {
        $this->aggregateVersion++;

        return [
            'order_id' => $this->orderId->orderId(),
            'company_id' => $this->orderId->companyId(),
            'order_date' => $this->orderDate->format('Y-m-d'),
            Aggregate::VERSION_COLUMN => $this->aggregateVersion
        ];
    }

    public static function fromState(array $aggregateState, array $childEntitiesByType)
    {
        $order = new self();

        $order->orderId = new OrderId($aggregateState['order_id'], (int)$aggregateState['company_id']);
        $dateTimeImmutable = DateTimeUtil::createDateTimeImmutable($aggregateState['order_date']);

        if (!$dateTimeImmutable instanceof DateTimeImmutable) {
            throw new \RuntimeException('Invalid date string from database');
        }
        $order->orderDate = $dateTimeImmutable;

        $order->lines = $childEntitiesByType[Line::class];

        $order->aggregateVersion = (int)$aggregateState[Aggregate::VERSION_COLUMN];

        return $order;
    }

    public static function tableName()
    {
        return 'orders';
    }

    public function identifier()
    {
        return [
            'order_id' => $this->orderId->orderId(),
            'company_id' => $this->orderId->companyId()
        ];
    }

    public static function identifierForQuery(AggregateId $aggregateId)
    {
        Assert::isInstanceOf($aggregateId, OrderId::class);
        /** @var OrderId $aggregateId */

        return [
            'order_id' => $aggregateId->orderId(),
            'company_id' => $aggregateId->companyId()
        ];
    }

    public function deletedChildEntities()
    {
        $deletedChildEntities = $this->deletedChildEntities;

        $this->deletedChildEntities = [];

        return $deletedChildEntities;
    }

    private function deleteChildEntity(ChildEntity $childEntity)
    {
        $this->deletedChildEntities[] = $childEntity;
    }

    public static function specifySchema(Schema $schema)
    {
        $table = $schema->createTable('orders');
        $table->addColumn('order_id', 'string');
        $table->addColumn('company_id', 'integer');
        $table->addColumn('order_date', 'date');
        $table->addColumn(Aggregate::VERSION_COLUMN, 'integer');
        $table->setPrimaryKey(['order_id', 'company_id']);

        Line::specifySchema($schema);
    }

    public function isNew()
    {
        return $this->isNew;
    }

    public function markAsPersisted()
    {
        $this->isNew = false;
    }

    /**
     * @return int
     */
    public function aggregateVersion()
    {
        return $this->aggregateVersion;
    }

    /**
     * @param int $aggregateVersion
     * @return void
     */
    public function setAggregateVersion($aggregateVersion)
    {
        Assert::integer($aggregateVersion);
        $this->aggregateVersion = $aggregateVersion;
    }
}
