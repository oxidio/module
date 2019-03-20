<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Module;

use Generator;
use IteratorAggregate;
use fn;

/**
 */
class Menu implements IteratorAggregate
{
    /**
     * @var string
     */
    private const NODES = [
        'OXMENU'   => 'MAINMENU',
        'MAINMENU' => 'SUBMENU',
        'SUBMENU'  => 'SUBMENU',
    ];

    /**
     * @var string|string[]
     */
    public $label;

    /**
     * @var string
     */
    public $class;

    /**
     * @var string
     */
    public $list;

    /**
     * @var array[][]|self[][]
     */
    protected $args;

    /**
     * @param string|string[] $label
     * @param array[]|self[] ...$args
     */
    public function __construct($label, ...$args)
    {
        $this->label = $label;
        $this->args  = $args;
    }

    public function getId(): string
    {
        if (is_string($this->label)) {
            return $this->label;
        }
        return reset($this->label);
    }

    /**
     * @inheritdoc
     */
    public function getIterator(): Generator
    {
        yield from $this->args;
    }

    protected function toString(string $node, string $prefix = '    '): Generator
    {
        yield "$prefix<{$node} id=\"{$this->getId()}\">";
        foreach ($this as $arg) {
            $arg instanceof self && yield fn\map($arg->toString(self::NODES[$node], $prefix . '    '))->string;
        }
        yield "$prefix</{$node}>";
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return fn\map($this->toString('OXMENU'))->string;
    }
}
