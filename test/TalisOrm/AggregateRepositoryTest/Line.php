<?php

namespace TalisOrm\AggregateRepositoryTest;

use Doctrine\DBAL\Schema\Schema;
use TalisOrm\AggregateId;
use TalisOrm\ChildEntity;
use TalisOrm\Schema\SpecifiesSchema;

final class Line implements ChildEntity, SpecifiesSchema
{
    /**
     * @var OrderId
     */
    private $orderId;

    /**
     * @var LineNumber
     */
    private $lineNumber;

    /**
     * @var ProductId
     */
    private $productId;

    /**
     * @var Quantity
     */
    private $quantity;

    /**
     * @var bool
     */
    private $isNew = true;

    private function __construct()
    {
    }

    /**
     * @param OrderId $orderId
     * @param LineNumber $lineNumber
     * @param ProductId $productId
     * @param Quantity $quantity
     * @return Line
     */
    public static function create(
        OrderId $orderId,
        LineNumber $lineNumber,
        ProductId $productId,
        Quantity $quantity
    ) {
        $line = new self();

        $line->orderId = $orderId;
        $line->lineNumber = $lineNumber;
        $line->productId = $productId;
        $line->quantity = $quantity;

        return $line;
    }

    /**
     * @param ProductId $productId
     * @param Quantity $quantity
     * @return void
     */
    public function update(ProductId $productId, Quantity $quantity)
    {
        $this->productId = $productId;
        $this->quantity = $quantity;
    }

    /**
     * @return LineNumber
     */
    public function lineNumber()
    {
        return $this->lineNumber;
    }

    /**
     * @return OrderId
     */
    public function orderId()
    {
        return $this->orderId;
    }

    /**
     * @return ProductId
     */
    public function productId()
    {
        return $this->productId;
    }

    /**
     * @return Quantity
     */
    public function quantity()
    {
        return $this->quantity;
    }

    public function state()
    {
        return [
            'order_id' => $this->orderId->orderId(),
            'company_id' => $this->orderId->companyId(),
            'line_number' => $this->lineNumber->asInt(),
            'product_id' => $this->productId->productId(),
            'quantity' => $this->quantity->asInt()
        ];
    }

    public static function fromState(array $state)
    {
        $line = new self();

        $line->orderId = new OrderId($state['order_id'], (int)$state['company_id']);
        $line->lineNumber = new LineNumber((int)$state['line_number']);
        $line->productId = new ProductId($state['product_id'], (int)$state['company_id']);
        $line->quantity = new Quantity((int)$state['quantity']);

        return $line;
    }

    public static function tableName()
    {
        return 'lines';
    }

    public function identifier()
    {
        return [
            'order_id' => $this->orderId->orderId(),
            'company_id' => $this->orderId->companyId(),
            'line_number' => $this->lineNumber->asInt()
        ];
    }

    public static function identifierForQuery(AggregateId $aggregateId)
    {
        if (!$aggregateId instanceof OrderId) {
            throw new \InvalidArgumentException('Expected an instance of OrderId');
        }

        return [
            'order_id' => $aggregateId->orderId(),
            'company_id' => $aggregateId->companyId()
        ];
    }

    public static function specifySchema(Schema $schema)
    {
        $table = $schema->createTable('lines');
        $table->addColumn('order_id', 'string');
        $table->addColumn('company_id', 'integer');
        $table->addColumn('line_number', 'integer');
        $table->addColumn('product_id', 'string');
        $table->addColumn('quantity', 'integer');
        $table->setPrimaryKey(['order_id', 'company_id', 'line_number']);
    }

    public function isNew()
    {
        return $this->isNew;
    }

    public function markAsPersisted()
    {
        $this->isNew = false;
    }
}
