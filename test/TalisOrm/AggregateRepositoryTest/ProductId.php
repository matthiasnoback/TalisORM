<?php

namespace TalisOrm\AggregateRepositoryTest;

use Webmozart\Assert\Assert;

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

    public function __construct($productId, $companyId)
    {
        Assert::string($productId);
        $this->productId = $productId;
        Assert::integer($companyId);
        $this->companyId = $companyId;
    }

    /**
     * @return string
     */
    public function productId()
    {
        return $this->productId;
    }

    /**
     * @return int
     */
    public function companyId()
    {
        return $this->companyId;
    }
}
