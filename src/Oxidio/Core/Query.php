<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core;

use php;
use Generator;
use Invoker\Exception\NotCallableException;
use Invoker\Reflection\CallableReflection;
use OxidEsales\Eshop\Core\Model\BaseModel;
use Oxidio;
use ReflectionClass;
use ReflectionParameter;

/**
 */
class Query extends AbstractSelectStatement
{
    /**
     * @param callable|string $from
     * @param callable|array $mapper
     * @param array[] $where
     */
    public function __construct($from = null, $mapper = null, ...$where)
    {
        if (php\isCallable($from)) {
            $this->mapper = $this->fromCallable($from);
        } else {
            $this->properties['view'] = $from;
        }

        if (php\isCallable($mapper)) {
            $this->mapper = $this->fromCallable($mapper);
            $this->where(...$where);
        } else if ($mapper) {
            $this->where($mapper, ...$where);
        }
    }

    protected static function args(array $row, ReflectionParameter ...$params): Generator
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
        } catch (NotCallableException $e) {
            return null;
        }

        if ($params[0]->isArray()) {
            return function(array $row) use($from, $params) {
                $args = php\values(static::args($row, ...array_slice($params, 1)));
                return $from($row, ...$args);
            };
        }

        return ($class = $params[0]->getClass())
            ? $this->fromCallableWithClass($from, $class, array_slice($params, 1)) : function(array $row) use($from, $params) {
                $args = php\values(static::args($row, $params[0], ...array_slice($params, 1)));
                return $from(...$args);
            };
    }

    protected function fromCallableWithClass($from, ReflectionClass $class, array $params): callable
    {
        $class->hasMethod('getViewName') && $this->properties['view'] = oxNew($class->name)->getViewName();
        if ($class->hasMethod('load')) {
            $params || $this->properties['columns'] = 'OXID';
            return function(array $row) use($from, $class, $params) {
                /** @var BaseModel $model */
                $model = oxNew($class->name);
                if ($model->load($row['OXID'])) {
                    $args = php\values(static::args($row, ...$params));
                    return $from($model, ...$args);
                }
                return null;
            };
        }

        return function(array $row) use($from, $class, $params) {
            $args = php\values(static::args($row, ...$params));
            return $from(oxNew($class->getName(), $row), ...$args);
        };
    }
}
