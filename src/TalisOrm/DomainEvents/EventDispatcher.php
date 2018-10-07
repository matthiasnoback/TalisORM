<?php

namespace TalisOrm\DomainEvents;

interface EventDispatcher
{
    public function dispatch(array $events);
}
