<?php

namespace TalisOrm;

interface AggregateId
{
    /**
     * @return string
     */
    public function __toString();
}
