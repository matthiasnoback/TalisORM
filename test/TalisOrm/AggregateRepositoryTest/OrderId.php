<?php
declare(strict_types=1);

namespace TalisOrm\AggregateRepositoryTest;

use TalisOrm\AggregateId;

final class OrderId implements AggregateId
{
    /**
     * @var string
     */
    private $orderId;

    /**
     * @var int
     */
    private $companyId;

    public function __construct(string $orderId, int $companyId)
    {
        $this->orderId = $orderId;
        $this->companyId = $companyId;
    }

    public function orderId(): string
    {
        return $this->orderId;
    }

    public function companyId(): int
    {
        return $this->companyId;
    }

    public function __toString(): string
    {
        return sprintf('%s-%d', $this->orderId, $this->companyId);
    }
}
