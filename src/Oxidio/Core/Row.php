<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core;

use ArrayAccess;
use fn;
use IteratorAggregate;

/**
 */
class Row implements ArrayAccess, IteratorAggregate
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
}
