<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Module;

use Generator;
use fn;

/**
 */
class Menu
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
     * @var string
     */
    protected $id;

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
        return $this->id ?: $this->id = is_string($this->label) ? $this->label : reset($this->label);
    }

    protected function toString(string $node, string $prefix = '    '): Generator
    {
        $cl = $this->class ? " cl=\"{$this->class}\"" : '';
        yield "$prefix<{$node} id=\"{$this->getId()}\"{$cl}>";
        foreach ($this->args as $arg) {
            foreach (is_iterable($arg) ? $arg : [$arg] as $key => $item) {
                if ($item instanceof static) { // sub menu
                    yield fn\map($item->toString(self::NODES[$node], $prefix . '    '))->string;
                } else if (is_numeric($key)) { // btn
                    $id = is_array($item) ? reset($item) : $item;
                    yield "$prefix    <BTN id=\"{$id}\" />";
                } else { // tab
                    $id = is_array($item) ? reset($item) : $item;
                    yield "$prefix    <TAB id=\"{$id}\" cl=\"{$key}\" />";
                }
            }
        }
        yield "$prefix</{$node}>";
    }

    /**
     * @param iterable $data
     * @return self[]|Generator
     */
    public static function create(iterable $data): Generator
    {
        foreach ($data as $key => $item) {
            $id = $class = null;
            if (!is_numeric($key)) {
                strpos($key, Menu\ADMIN) === 0 ? $id = end($id = explode('/', $key)) : $class = $key;
            }

            if ($item instanceof static) {
                $id && $item->id = $id;
                $class && $item->class = $class;
                yield $item;
            } else if ($id) { // merge
                $known = new static(null, is_iterable($item) ? static::create($item) : []);
                $known->id = $id;
                yield $known;
            } else {
                yield $key => $item;
            }
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        try {
            return fn\map($this->toString('OXMENU'))->string;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
