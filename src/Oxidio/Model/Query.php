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
 * @property-read ReflectionParameter[] $params
 */
class Query implements IteratorAggregate, Countable
{
    use Query\SelectTrait;

    /**
     * @param callable|string $from
     * @param callable|array $mapper
     * @param array[] $where
     */
    public function __construct($from = null, $mapper = null, ...$where)
    {
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
            $this->properties['params'] = array_slice($params, 1);
        } catch (NotCallableException $e) {
            return null;
        }

        if ($params[0]->isArray()) {
            return function(array $row) use($from) {
                $args = fn\values(static::args($row, ...$this->params));
                return $from($row, ...$args);
            };
        }

        return ($class = $params[0]->getClass())
            ? $this->fromCallableWithClass($from, $class) : function(array $row) use($from, $params) {
                $args = fn\values(static::args($row, $params[0], ...$this->params));
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
}
