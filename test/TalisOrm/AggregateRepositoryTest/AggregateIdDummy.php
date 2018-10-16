<?php

namespace TalisOrm\AggregateRepositoryTest;

use TalisOrm\AggregateId;

final class AggregateIdDummy implements AggregateId
{
    public function __toString()
    {
        return 'irrelevant';
    }
}
