<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core;

use ArrayAccess;
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
abstract class AbstractSelectStatement extends AbstractConditionalStatement implements
    ArrayAccess,
    IteratorAggregate,
    Countable
{
    use fn\ArrayAccessTrait;

    protected $orderTerm;
    protected $limitTerm;
    protected $mapper;

    /**
     * @return array
     */
    protected function data(): array
    {
        if (!isset($this->props['rows'])) {
            $this->props['rows'] = fn\traverse($this->props['it'] ?? $this->getIterator());
        }
        return $this->props['rows'];
    }

    /**
     * @inheritDoc
     */
    public function where(...$terms)
    {
        unset($this->props['rows']);
        return parent::where(...$terms);
    }

    /**
     * @param string[]|array[] ...$terms
     *
     * @return $this
     */
    public function orderBy(...$terms): self
    {
        unset($this->props['rows']);
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
        unset($this->props['rows']);
        $this->props['limit'] = $limit;
        $this->props['start'] = $start;
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
        unset($this->props['rows']);
        return $this->props['it'] = ($this->db)($this, ...($this->mapper ? [$this->mapper] : []));
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
