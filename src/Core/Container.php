<?php
namespace Core;

class Container
{
    private array $bindings = [];

    public function set(string $id, $value): void
    {
        $this->bindings[$id] = $value;
    }

    public function get(string $id)
    {
        return $this->bindings[$id] ?? null;
    }
}
