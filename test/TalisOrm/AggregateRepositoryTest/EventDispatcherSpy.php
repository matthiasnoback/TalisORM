<?php
declare(strict_types=1);

namespace TalisOrm\AggregateRepositoryTest;

use TalisOrm\DomainEvents\EventDispatcher;

final class EventDispatcherSpy implements EventDispatcher
{
    private $dispatchedEvents = [];

    public function dispatch(...$events)
    {
        foreach ($events as $event) {
            $this->dispatchedEvents[] = $event;
        }
    }

    public function dispatchedEvents(): array
    {
        return $this->dispatchedEvents;
    }
}
