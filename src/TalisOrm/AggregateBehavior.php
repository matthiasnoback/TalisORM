<?php
declare(strict_types=1);

namespace TalisOrm;

use TalisOrm\DomainEvents\EventRecordingCapabilities;

trait AggregateBehavior
{
    use EventRecordingCapabilities;

    /**
     * @var ChildEntity[]
     */
    private $deletedChildEntities = [];

    /**
     * @var bool
     */
    private $isNew = true;

    /**
     * @var int
     */
    private $aggregateVersion = 0;

    public function deletedChildEntities(): array
    {
        $deletedChildEntities = $this->deletedChildEntities;

        $this->deletedChildEntities = [];

        return $deletedChildEntities;
    }

    private function deleteChildEntity(ChildEntity $childEntity): void
    {
        $this->deletedChildEntities[] = $childEntity;
    }

    public function isNew(): bool
    {
        return $this->isNew;
    }

    public function markAsPersisted(): void
    {
        $this->isNew = false;
    }

    public function aggregateVersion(): int
    {
        return $this->aggregateVersion;
    }
}
