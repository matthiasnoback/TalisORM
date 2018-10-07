<?php

namespace TalisOrm\AggregateRepositoryTest;

use TalisOrm\DomainEvents\EventDispatcher;

final class EventDispatcherSpy implements EventDispatcher
{
    private $dispatchedEvents = [];

    public function dispatch(array $events)
    {
        foreach ($events as $event) {
            $this->dispatchedEvents[] = $event;
        }
    }

    /**
     * @return object[]
     */
    public function dispatchedEvents()
    {
        return $this->dispatchedEvents;
    }
}
