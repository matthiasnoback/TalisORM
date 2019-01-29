<?php

namespace TalisOrm;

interface AggregateId
{
    public function __toString(): string;
}
