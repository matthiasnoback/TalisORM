<?php
declare(strict_types=1);

namespace TalisOrm\AggregateRepositoryTest;

use Webmozart\Assert\Assert;

final class Quantity
{
    /**
     * @var int
     */
    private $quantity;

    public function __construct(int $quantity)
    {
        Assert::greaterThan($quantity, 0);
        $this->quantity = $quantity;
    }

    public function asInt(): int
    {
        return $this->quantity;
    }
}
