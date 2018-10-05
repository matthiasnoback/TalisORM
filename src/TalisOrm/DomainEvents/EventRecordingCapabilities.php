<?php
declare(strict_types=1);

namespace TalisOrm\DomainEvents;

use InvalidArgumentException;
use function is_object;
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
     */
    protected function recordThat($event): void
    {
        Assert::object($event);

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
