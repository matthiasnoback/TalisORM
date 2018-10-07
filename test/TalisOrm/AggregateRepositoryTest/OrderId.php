<?php

namespace TalisOrm\AggregateRepositoryTest;

use TalisOrm\AggregateId;
use Webmozart\Assert\Assert;

final class OrderId implements AggregateId
{
    /**
     * @var string
     */
    private $orderId;

    /**
     * @var int
     */
    private $companyId;

    public function __construct($orderId, $companyId)
    {
        Assert::string($orderId);
        $this->orderId = $orderId;
        Assert::integer($companyId);
        $this->companyId = $companyId;
    }

    /**
     * @return string
     */
    public function orderId()
    {
        return $this->orderId;
    }

    /**
     * @return int
     */
    public function companyId()
    {
        return $this->companyId;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('%s-%d', $this->orderId, $this->companyId);
    }
}
