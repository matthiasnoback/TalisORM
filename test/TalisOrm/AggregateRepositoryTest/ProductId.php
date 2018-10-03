<?php
declare(strict_types=1);

namespace TalisOrm\AggregateRepositoryTest;

final class ProductId
{
    /**
     * @var string
     */
    private $productId;

    /**
     * @var int
     */
    private $companyId;

    public function __construct(string $productId, int $companyId)
    {
        $this->productId = $productId;
        $this->companyId = $companyId;
    }

    public function productId(): string
    {
        return $this->productId;
    }

    public function companyId(): int
    {
        return $this->companyId;
    }
}
