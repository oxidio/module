<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core;

use Countable;
use IteratorAggregate;
use fn;
use Oxidio;

/**
 * @property-read callable $db
 * @property-read int $limit
 * @property-read int $start
 * @property-read int $total
 * @property-read string $view
 * @property-read string $columns
 */
abstract class AbstractSelectStatement implements IteratorAggregate, Countable
{
    use fn\PropertiesReadOnlyTrait;

    protected $data = [];
    protected $whereTerm;
    protected $orderTerm;
    protected $limitTerm;
    protected $mapper;

    /**
     * @inheritdoc
     */
    protected function property(string $name, bool $assert)
    {
        if (fn\hasKey($name, $this->data)) {
            return $assert ? $this->data[$name] : true;
        }
        if (method_exists($this, "resolve$name")) {
            return $assert ? $this->{"resolve$name"}() : true;
        }
        $assert && fn\fail($name);
        return false;
    }

    /**
     * @param callable $db
     *
     * @return $this
     */
    public function withDb(callable $db): self
    {
        $this->data['db'] = $db;
        return $this;
    }

    protected function resolveDb(): Oxidio\Core\Database
    {
        return Oxidio\db();
    }

    protected function getColumnName($candidate): string
    {
        return $candidate;
    }

    /**
     * @param array ...$terms
     *
     * @return $this
     */
    public function where(...$terms): self
    {
        $this->whereTerm = $this->buildWhere($terms);
        return $this;
    }

    /**
     * @param array $terms
     *
     * @return string
     */
    public function buildWhere(array $terms): string
    {
        return implode(' OR ', fn\traverse($terms, function ($term) {
            if ($term = is_iterable($term) ? implode(' AND ', fn\traverse($term, function($candidate, $column) {
                $value = $candidate;
                $operator = null;
                if (is_iterable($candidate)) {
                    $column = $candidate['column'] ?? $column;
                    $operator = $candidate['op'] ?? $candidate[0] ?? null;
                    $value = $candidate['value'] ?? $candidate[1] ?? null;
                }

                if ($value === null) {
                    $value = 'NULL';
                    $operator = $operator ?: 'IS';
                } else {
                    $value = "'{$value}'";
                    $operator = $operator ?: '=';
                }
                return "{$this->getColumnName($column)} {$operator} {$value}";
            })) : $term) {
                return "($term)";
            }
            return null;
        }));
    }

    /**
     * @param string[]|array[] ...$terms
     *
     * @return $this
     */
    public function orderBy(...$terms): self
    {
        $this->orderTerm = $this->buildOrderBy($terms);
        return $this;
    }

    public function buildOrderBy(array $terms): string
    {
        return implode(', ', fn\traverse($terms, function ($term) {
            return implode(', ',
                fn\traverse(is_iterable($term) ? $term : (array)$term, function ($direction, $property) {
                    if (is_numeric($property)) {
                        $property = $direction;
                        $direction = 'ASC';
                    }

                    return "{$this->getColumnName($property)} {$direction}";
                }));
        }));
    }

    /**
     * @param int $limit 0 => unlimited
     * @param int $start 0 => first row
     *
     * @return $this
     */
    public function limit($limit, $start = 0): self
    {
        $this->data['limit'] = $limit;
        $this->data['start'] = $start;
        $this->limitTerm = $this->buildLimit($limit, $start);
        return $this;
    }

    protected function resolveLimit(): int
    {
        return 0;
    }

    protected function resolveStart(): int
    {
        return 0;
    }

    protected function resolveColumns(): string
    {
        return '*';
    }

    protected function resolveView()
    {
        fn\fail(__METHOD__);
    }

    /**
     * @param int $limit
     * @param int $start
     *
     * @return string
     */
    public function buildLimit($limit, $start = 0): string
    {
        if ($start && !$limit) {
            $limit = PHP_INT_MAX;
        }
        return $limit ? "{$start}, {$limit}" : '';
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        $select = "SELECT {$this->columns}";
        $from = "\nFROM {$this->view}";
        $where = $this->whereTerm ? "\nWHERE {$this->whereTerm}" : null;
        $order = $this->orderTerm ? "\nORDER BY {$this->orderTerm}" : null;
        $limit = $this->limitTerm ? "\nLIMIT {$this->limitTerm}" : null;

        return $select . $from . $where . $order . $limit;
    }

    /**
     * @inheritdoc
     *
     * @return fn\Map
     */
    public function getIterator(): fn\Map
    {
        return ($this->db)($this, ...($this->mapper ? [$this->mapper] : []));
    }

    /**
     * @return int
     */
    public function count(): int
    {
        $count = $this->total;
        if ($start = max($this->start, 0)) {
            $count = max($count - $start, 0);
        }
        if ($limit = max($this->limit, 0)) {
            $count = min($count, $limit);
        }

        return $count;
    }

    /**
     * @return int
     */
    protected function resolveTotal(): int
    {
        $select = 'SELECT COUNT(*) AS total';
        $from = "\nFROM {$this->view}";
        $where = $this->whereTerm ? "\nWHERE {$this->whereTerm}" : null;

        return (int) ($this->db)($select . $from . $where)[0]['total'];
    }
}
