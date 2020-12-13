<?php

namespace TalisOrm\DomainEvents;

interface EventDispatcher
{
    /**
     * @param object[] $events
     */
    public function dispatch(array $events): void;
}
