<?php

namespace TalisOrm;

use Webmozart\Assert\Assert;

final class ImmutableState implements State
{
    /**
     * @var array
     */
    private $values = [];

    public function __construct(array $values = [])
    {
        $this->values = $values;
    }

    public function withString(string $key, string $value): State
    {
        $state = clone $this;
        $state->values[$key] = $value;

        return $state;
    }

    public function withInt(string $key, int $value): State
    {
        $state = clone $this;
        $state->values[$key] = $value;

        return $state;
    }

    public function toArray(): array
    {
        return $this->values;
    }

    public function string(string $key)
    {
        Assert::keyExists($this->values, $key);

        return $this->values[$key];
    }

    public function int(string $key)
    {
        Assert::keyExists($this->values, $key);

        return (int)$this->values[$key];
    }

    public function count(): int
    {
        return count($this->values);
    }
}
