<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use php;
use JsonSerializable;
use Oxidio;

/**
 * @property-read int $limit
 * @property-read int $start
 * @property-read int $total
 * @property-read string $columns
 * @property-read array orderTerms
 */
abstract class AbstractSelectStatement extends AbstractConditionalStatement implements
    ArrayAccess,
    JsonSerializable,
    IteratorAggregate,
    Countable
{
    /**
     * @see \php\PropertiesTrait::propResolver
     * @uses resolveLimit, resolveStart, resolveTotal, resolveColumns, resolveOrderTerms
     */

    use php\ArrayAccessTrait;

    protected $mapper;

    /**
     * @return array
     */
    protected function data(): array
    {
        if ($this->data === null) {
            $this->data = php\traverse($this->properties['it'] ?? $this->getIterator());
        }
        return $this->data;
    }

    /**
     * @inheritDoc
     */
    public function where(...$terms)
    {
        $this->data = null;
        return parent::where(...$terms);
    }

    /**
     * @param string[]|array[] ...$terms
     *
     * @return $this
     */
    public function orderBy(...$terms): self
    {
        $this->data = null;
        $this->properties['orderTerms'] = array_filter($terms);
        return $this;
    }

    /**
     * @param array $terms
     * @param string $prefix
     *
     * @return string
     */
    public function buildOrderBy(array $terms, string $prefix = "\nORDER BY "): string
    {
        $order = implode(', ', php\traverse($terms, function ($term) {
            return implode(', ',
                php\traverse(is_iterable($term) ? $term : (array)$term, function ($direction, $property) {
                    if (is_numeric($property)) {
                        $property = $direction;
                        $direction = 'ASC';
                    }

                    return "{$this->getColumnName($property)} {$direction}";
                }));
        }));

        return $order ? $prefix . $order : $order;
    }

    /**
     * @param int $limit 0 => unlimited
     * @param int $start 0 => first row
     *
     * @return $this
     */
    public function limit($limit, $start = 0): self
    {
        $this->data = null;
        $this->properties['limit'] = $limit;
        $this->properties['start'] = $start;
        return $this;
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
        return $this->getSql($this->start, $this->limit);
    }

    /**
     * @param int $start
     * @param int $limit
     * @return string
     */
    public function getSql($start, $limit = 50): string
    {
        $limitTerm = $this->buildLimit($limit, $start);
        $limitTerm = $limitTerm ? "\nLIMIT {$limitTerm}" : null;

        return "SELECT {$this->columns}\nFROM {$this->view}"
            . $this->buildWhere($this->whereTerms)
            . $this->buildOrderBy($this->orderTerms)
            . $limitTerm;
    }

    /**
     * @inheritdoc
     *
     * @return php\Map
     */
    public function getIterator(): php\Map
    {
        $this->data = null;
        return $this->properties['it'] = ($this->db)($this, ...($this->mapper ? [$this->mapper] : []));
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
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return $this->data();
    }

    /**
     * @see $total
     */
    protected function resolveTotal(): int
    {
        return (int) ($this->db)(
            'SELECT COUNT(*) AS total' .
            "\nFROM {$this->view}" .
            $this->buildWhere($this->whereTerms)
        )[0]['total'];
    }


    /**
     * @see $limit
     */
    protected function resolveLimit(): int
    {
        return 0;
    }

    /**
     * @see $start
     */
    protected function resolveStart(): int
    {
        return 0;
    }

    /**
     * @see $columns
     */
    protected function resolveColumns(): string
    {
        return '*';
    }

    /**
     * @see $orderTerms
     */
    public function resolveOrderTerms(): array
    {
        return [];
    }
}
