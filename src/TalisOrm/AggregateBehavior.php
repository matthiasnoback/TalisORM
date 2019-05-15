<?php
declare(strict_types=1);

namespace TalisOrm;

use TalisOrm\DomainEvents\EventRecordingCapabilities;
use Webmozart\Assert\Assert;

trait AggregateBehavior
{
    use EventRecordingCapabilities;

    /**
     * @var array
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

    private function deleteChildEntity(ChildEntity $childEntity)
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

    /**
     * @return int
     */
    public function aggregateVersion()
    {
        return $this->aggregateVersion;
    }
}
