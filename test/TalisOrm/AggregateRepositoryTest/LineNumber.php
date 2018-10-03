<?php
declare(strict_types=1);

namespace TalisOrm\AggregateRepositoryTest;

final class LineNumber
{
    /**
     * @var int
     */
    private $lineNumber;

    public function __construct(int $lineNumber)
    {
        $this->lineNumber = $lineNumber;
    }

    public function asInt(): int
    {
        return $this->lineNumber;
    }
}
