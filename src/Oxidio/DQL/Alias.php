<?php declare(strict_types=1);
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\DQL;

use ArrayAccess;
use Php\Php;

class Alias implements ArrayAccess
{
    private $view;
    private $columns;

    public function __construct(?string $view = null, array ...$columns)
    {
        $this->view = $view;
        $columns = Php::arr($columns, function (array $columns) {
            yield Php::arr($columns, function ($column, $alias) {
                if ($column instanceof Column) {
                    yield [$column->alias ?: $alias] => Php::str($column->name, ['v' => $this->view]);
                } else {
                    $alias = is_numeric($alias) ? $column : $alias;
                    yield [$alias] => $this->concat('.', "`$column`");
                }
            });
        });
        $this->columns = array_merge([], ...$columns);
    }

    private function concat(?string ...$strings): ?string
    {
        if ($this->view) {
            return "`{$this->view}`" . implode('', $strings);
        }
        return $strings[count($strings) - 1] ?? null;
    }

    public function __invoke(string ...$aliases): string
    {
        $pieces = [];
        foreach ($aliases as $candidate) {
            foreach ($candidate === '*' ? array_keys($this->columns) : [$candidate] as $alias) {
                $pieces[] = "{$this->get($alias, true)} AS `$alias`";
            }
        }
        return implode(",\n", $pieces);
    }

    public function v(string $view): string
    {
        return $this->view ? "`{$view}` AS `{$this->view}`" : "`{$view}`";
    }

    public function offsetExists($alias): bool
    {
        return isset($this->columns[$alias]);
    }

    public function get($alias, bool $asColumn = false): string
    {
        if ($alias instanceof Column) {
            $alias = $alias->alias ?: $alias->name;
        }
        if (!($column = $this->columns[$alias] ?? null) || !$asColumn) {
            return $this->concat('.', "`$alias`");
        }
        return $column;
    }

    public function offsetGet($alias)
    {
        return $this->get($alias, true);
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
