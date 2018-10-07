<?php

namespace TalisOrm\AggregateRepositoryTest;

use Webmozart\Assert\Assert;

final class Quantity
{
    /**
     * @var int
     */
    private $quantity;

    public function __construct($quantity)
    {
        Assert::integer($quantity);
        Assert::greaterThan($quantity, 0);
        $this->quantity = $quantity;
    }

    /**
     * @return int
     */
    public function asInt()
    {
        return $this->quantity;
    }
}
