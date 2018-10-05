<?php

namespace TalisOrm\DomainEvents;

interface EventDispatcher
{
    public function dispatch(...$events);
}
