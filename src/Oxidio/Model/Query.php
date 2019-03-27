<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Model;

use Countable;
use fn;
use Invoker\Exception\NotCallableException;
use Invoker\Reflection\CallableReflection;
use IteratorAggregate;
use OxidEsales\Eshop\Core\Model\BaseModel;
use Oxidio;
use ReflectionClass;
use ReflectionParameter;

/**
 * @property-read int $limit
 * @property-read int $start
 * @property-read string $view
 * @property-read string $columns
 * @property-read ReflectionParameter|null $param
 * @property-read ReflectionParameter[] $params
 */
class Query implements IteratorAggregate, Countable
{
    use fn\PropertiesReadOnlyTrait;

    protected const DEFAULT = [
        'limit' => 0,
        'start' => 0,
        'view' => '',
        'columns' => '*',
        'param' => null,
        'params' => [],
    ];

    private $whereTerm;
    private $orderTerm;
    private $limitTerm;
    private $mapper;

    /**
     * @param callable|string $from
     * @param callable|array $mapper
     * @param array[] $where
     */
    public function __construct($from = null, $mapper = null, ...$where)
    {
        $this->initProperties();
        if (fn\isCallable($from)) {
            $this->mapper = $this->fromCallable($from);
        } else {
            $this->properties['view'] = $from;
        }

        if (fn\isCallable($mapper)) {
            $this->mapper = $this->fromCallable($mapper);
            $this->where(...$where);
        } else if ($mapper) {
            $this->where($mapper, ...$where);
        }

    }

    protected static function args(array $row, ReflectionParameter ...$params): \Generator
    {
        foreach ($params as $param) {
            $name = $param->getName();
            yield $row[$name] ?? $row[$name = strtoupper($name)] ?? $row['OX' . $name] ?? $param->getDefaultValue();
        }
    }

    /**
     * function(array $row, string ...$column)
     * function(BaseModel $model, string ...$column)
     * function(Object $model, string ...$column)
     * function(string ...$column)
     *
     * @param callable $from
     * @return callable|null
     */
    protected function fromCallable($from): ?callable
    {
        try {
            $params = CallableReflection::create($from)->getParameters();
            $this->properties['param']  = $params[0];
            $this->properties['params'] = array_slice($params, 1);
        } catch (NotCallableException $e) {
            return null;
        }

        if ($this->param->isArray()) {
            return function(array $row) use($from) {
                $args = fn\values(static::args($row, ...$this->params));
                return $from($row, ...$args);
            };
        }

        return ($class = $this->param->getClass())
            ? $this->fromCallableWithClass($from, $class) : function(array $row) use($from) {
                $args = fn\values(static::args($row, $this->param, ...$this->params));
                return $from(...$args);
            };

    }

    protected function fromCallableWithClass($from, ReflectionClass $class): callable
    {
        $class->hasMethod('getViewName') && $this->properties['view'] = oxNew($class->name)->getViewName();
        if ($class->hasMethod('load')) {
            $this->params || $this->properties['columns'] = 'OXID';
            return function(array $row) use($from, $class) {
                /** @var BaseModel $model */
                $model = oxNew($class->name);
                if ($model->load($row['OXID'])) {
                    $args = fn\values(static::args($row, ...$this->params));
                    return $from($model, ...$args);
                }
                return null;
            };
        }

        return function(array $row) use($from, $class) {
            $args = fn\values(static::args($row, ...$this->params));
            return $from(oxNew($class->getName(), $row), ...$args);
        };
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
            if ($term = is_iterable($term) ? implode(' AND ', fn\traverse($term, $this)) : $term) {
                return "($term)";
            }
            return null;
        }));
    }

    /**
     * @param mixed $candidate
     * @param string $column
     *
     * @return string
     */
    public function __invoke($candidate, $column): string
    {
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

        return "`{$this->getColumnName($column)}` {$operator} {$value}";
    }

    /**
     * @param string[]|array[] ...$terms
     *
     * @return self
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

                    return "`{$this->getColumnName($property)}` {$direction}";
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
        $this->properties['limit'] = $limit;
        $this->properties['start'] = $start;
        $this->limitTerm = $this->buildLimit($limit, $start);
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
        $select = "SELECT {$this->columns}";
        $from = "\nFROM `{$this->view}`";
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
        return Oxidio\select($this, ...($this->mapper ? [$this->mapper] : []));
    }

    /**
     * @return int
     */
    public function count(): int
    {
        $count = $this->total();
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
    public function total(): int
    {
        $select = 'SELECT COUNT(*) AS total';
        $from = "\nFROM `{$this->view}`";
        $where = $this->whereTerm ? "\nWHERE {$this->whereTerm}" : null;
        return (int) Oxidio\select($select . $from . $where)[0]['total'];
    }
}
