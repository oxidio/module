<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core;

use ArrayAccess;
use fn;
use IteratorAggregate;
use JsonSerializable;

/**
 */
class Row implements ArrayAccess, IteratorAggregate, JsonSerializable
{
    use fn\ArrayAccessTrait;

    /**
     * @var iterable
     */
    protected $children = [];

    /**
     * @param iterable $data
     */
    public function __construct(iterable $data)
    {
        $this->data = array_change_key_case(fn\traverse($data));
    }

    /**
     * @param iterable $children
     * @return $this
     */
    public function withChildren(iterable $children): self
    {
        $this->children = $children;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getIterator()
    {
        return fn\map($this->children);
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return $this->data;
    }

    /**
     * @param mixed ...$args
     * @return mixed|fn\Map\Value|null
     */
    public function __invoke(...$args)
    {
        if (!$args) {
            return $this->data;
        }

        if (count($args) > 1) {
            return fn\mapKey($this[$args[0]] ?? null)->andValue($this[$args[1]] ?? null);
        }
        if (is_iterable($args[0])) {
            $array = [];
            foreach ($args[0] as $column => $alias) {
                is_int($column) && $column = $alias;
                if ($this->offsetExists($column)) {
                    $array[$alias] = $this[$column];
                }
            }
            return $array;
        }
        return $this[$args[0]] ?? null;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)($this['oxid'] ?? null);
    }
}
