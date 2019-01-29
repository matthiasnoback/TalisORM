<?php

namespace TalisOrm\DomainEvents;

/**
 * Use this trait to prevent code duplication in any aggregate that implements RecordsEvents.
 *
 * @see \TalisOrm\Aggregate::releaseEvents()
 */
trait EventRecordingCapabilities
{
    private $events = [];

    /**
     * Use this method inside your aggregate to record new domain events.
     */
    protected function recordThat(object $event): void
    {
        $this->events[] = $event;
    }

    /**
     * @see \TalisOrm\Aggregate::releaseEvents()
     * @return object[]
     */
    public function releaseEvents(): array
    {
        $events = $this->events;

        $this->events = [];

        return $events;
    }
}
