<?php

namespace TalisOrm\AggregateRepositoryTest\SimpleAggregate;

use TalisOrm\AggregateId;
use Webmozart\Assert\Assert;

final class SimpleAggregateId implements AggregateId
{
    /**
     * @var int
     */
    private $id;

    public function __construct($id)
    {
        Assert::integer($id);
        $this->id = $id;
    }

    public function __toString()
    {
        return (string)$this->id;
    }

    public function id()
    {
        return $this->id;
    }
}
