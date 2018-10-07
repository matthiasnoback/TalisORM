<?php

namespace TalisOrm\DomainEvents;

use Webmozart\Assert\Assert;

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
     *
     * @param object $event
     * @return void
     */
    protected function recordThat($event)
    {
        Assert::object($event);

        $this->events[] = $event;
    }

    /**
     * @see \TalisOrm\Aggregate::releaseEvents()
     * @return object[]
     */
    public function releaseEvents()
    {
        $events = $this->events;

        $this->events = [];

        return $events;
    }
}
