<?php declare(strict_types=1);
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\DQL;

use ArrayAccess;
use Php\Php;

class Alias implements ArrayAccess
{
    private $alias;
    private $columns;

    public function __construct(string $alias, array ...$columns)
    {
        $this->alias = $alias;
        $this->columns = array_merge([], ...$columns);
    }

    public function __invoke(string ...$aliases): string
    {
        $pieces = [];
        foreach ($aliases as $candidate) {
            foreach ($candidate === '*' ? array_keys($this->columns) : [$candidate] as $alias) {
                $pieces[] = "{$this[$alias]} AS `$alias`";
            }
        }
        return implode(",\n", $pieces);
    }

    public function v(string $view): string
    {
        return "`{$view}` AS `{$this->alias}`";
    }

    public function offsetExists($alias): bool
    {
        return isset($this->columns[$alias]);
    }

    public function offsetGet($alias)
    {
        $column = $this->columns[$alias] ?? $alias;
        return $this->alias ? "`{$this->alias}`.`$column`" : "`$column`";
    }

    public function offsetSet($alias, $column): void
    {
        Php::fail($alias);
    }

    public function offsetUnset($alias): void
    {
        Php::fail($alias);
    }
}
