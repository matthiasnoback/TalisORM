<?php

namespace TalisOrm\AggregateRepositoryTest;

use Webmozart\Assert\Assert;

final class LineNumber
{
    /**
     * @var int
     */
    private $lineNumber;

    public function __construct($lineNumber)
    {
        Assert::integer($lineNumber);
        $this->lineNumber = $lineNumber;
    }

    /**
     * @return int
     */
    public function asInt()
    {
        return $this->lineNumber;
    }
}
