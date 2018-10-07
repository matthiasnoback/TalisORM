<?php

namespace TalisOrm;

interface State extends \Countable
{
    public function withString(string $key, string $value): State;
    public function withInt(string $key, int $value): State;
    public function toArray(): array;

    public function string(string $key);
    public function int(string $key);
}
