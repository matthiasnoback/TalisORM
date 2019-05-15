<?php
declare(strict_types=1);

namespace TalisOrm;

trait ChildEntityBehavior
{

    /**
     * @var bool
     */
    private $isNew = true;

    public function isNew(): bool
    {
        return $this->isNew;
    }

    public function markAsPersisted(): void
    {
        $this->isNew = false;
    }
}
