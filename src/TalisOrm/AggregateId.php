<?php
declare(strict_types=1);

namespace TalisOrm;

interface AggregateId
{
    public function __toString(): string;
}
