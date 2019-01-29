<?php

namespace TalisOrm\AggregateRepositoryTest;

use TalisOrm\AggregateId;
use Webmozart\Assert\Assert;

final class FromStateDoesNotReturnAnAggregateId implements AggregateId
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

    public function __toString(): string
    {
        return (string)$this->id;
    }

    public function id()
    {
        return $this->id;
    }
}
