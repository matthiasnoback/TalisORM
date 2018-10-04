<?php
declare(strict_types=1);

namespace TalisOrm\AggregateRepositoryTest;

use Doctrine\DBAL\Schema\Schema;
use TalisOrm\AggregateId;
use TalisOrm\ChildEntity;
use TalisOrm\Entity;
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

    private function __construct()
    {
    }

    public static function create(
        OrderId $orderId,
        LineNumber $lineNumber,
        ProductId $productId,
        Quantity $quantity
    ): Line {
        $line = new self();

        $line->orderId = $orderId;
        $line->lineNumber = $lineNumber;
        $line->productId = $productId;
        $line->quantity = $quantity;

        return $line;
    }

    public function update(ProductId $productId, Quantity $quantity): void
    {
        $this->productId = $productId;
        $this->quantity = $quantity;
    }

    public function lineNumber(): LineNumber
    {
        return $this->lineNumber;
    }

    public function orderId(): OrderId
    {
        return $this->orderId;
    }

    public function productId(): ProductId
    {
        return $this->productId;
    }

    public function quantity(): Quantity
    {
        return $this->quantity;
    }

    public function state(): array
    {
        return [
            'order_id' => $this->orderId->orderId(),
            'company_id' => $this->orderId->companyId(),
            'line_number' => $this->lineNumber->asInt(),
            'product_id' => $this->productId->productId(),
            'quantity' => $this->quantity->asInt()
        ];
    }

    public static function fromState(array $state): Entity
    {
        $line = new self();

        $line->orderId = new OrderId($state['order_id'], (int)$state['company_id']);
        $line->lineNumber = new LineNumber((int)$state['line_number']);
        $line->productId = new ProductId($state['product_id'], (int)$state['company_id']);
        $line->quantity = new Quantity((int)$state['quantity']);

        return $line;
    }

    public static function tableName(): string
    {
        return 'lines';
    }

    public function identifier(): array
    {
        return [
            'order_id' => $this->orderId->orderId(),
            'company_id' => $this->orderId->companyId(),
            'line_number' => $this->lineNumber->asInt()
        ];
    }

    public static function identifierForQuery(AggregateId $aggregateId): array
    {
        if (!$aggregateId instanceof OrderId) {
            throw new \InvalidArgumentException('Expected an instance of OrderId');
        }

        return [
            'order_id' => $aggregateId->orderId(),
            'company_id' => $aggregateId->companyId()
        ];
    }

    public static function specifySchema(Schema $schema): void
    {
        $table = $schema->createTable('lines');
        $table->addColumn('order_id', 'string');
        $table->addColumn('company_id', 'integer');
        $table->addColumn('line_number', 'integer');
        $table->addColumn('product_id', 'string');
        $table->addColumn('quantity', 'integer');
        $table->addUniqueIndex(['order_id', 'company_id', 'line_number']);
    }
}
