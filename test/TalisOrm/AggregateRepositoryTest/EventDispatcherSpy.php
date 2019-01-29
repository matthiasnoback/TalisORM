<?php

namespace TalisOrm\AggregateRepositoryTest;

use TalisOrm\DomainEvents\EventDispatcher;

final class EventDispatcherSpy implements EventDispatcher
{
    private $dispatchedEvents = [];

    public function dispatch(array $events): void
    {
        foreach ($events as $event) {
            $this->dispatchedEvents[] = $event;
        }
    }

    /**
     * @return object[]
     */
    public function dispatchedEvents(): array
    {
        return $this->dispatchedEvents;
    }
}
