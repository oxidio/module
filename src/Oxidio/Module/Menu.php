<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Module;

use Generator;
use fn;
use IteratorAggregate;

/**
 *
 */
class Menu extends MenuNode implements IteratorAggregate
{
    /**
     * @var string
     */
    protected const TAGS = ['OXMENU' => 'MAINMENU', 'MAINMENU' => 'SUBMENU', 'SUBMENU' => 'SUBMENU'];

    /**
     * @var string
     */
    protected const INDENT = '    ';

    /**
     * @var bool
     */
    protected $merged = false;

    /**
     * @var self[]
     */
    public $menus = [];

    /**
     * @var MenuNode[]
     */
    public $tabs = [];

    /**
     * @var MenuNode[]
     */
    public $buttons = [];

    /**
     * @var callable
     */
    public $callback;

    /**
     * @param string|string[] $props
     * @param array[]|self[] ...$args
     */
    public function __construct($props, ...$args)
    {
        parent::__construct($props);
        foreach ($args as $arg) {
            foreach (is_iterable($arg) && !$arg instanceof static ? $arg : [$arg] as $key => $item) {
                if ($item instanceof static) {
                    $this->menus[] = $item;
                    continue;
                }
                $item = $item instanceof MenuNode ? $item : new MenuNode($item);
                if (is_numeric($key)) {
                    $this->buttons[] = $item;
                } else {
                    $item->class  = $key;
                    $this->tabs[] = $item;
                }
            }
        }
    }

    /**
     * @return fn\Map
     */
    public function getIterator(): fn\Map
    {
        return fn\map($this->menus);
    }

    /**
     * @see \OxidEsales\EshopCommunity\Application\Controller\Admin\NavigationTree
     *
     * @param string $tag
     * @param string $indent
     * @return Generator
     */
    protected function toString(string $tag, string $indent = self::INDENT): Generator
    {
        $attrs = parent::__toString();
        $newIndent = $indent . self::INDENT;

        yield "$indent<$tag $attrs>";
        foreach ($this->menus as $item) {
            yield fn\map($item->toString(self::TAGS[$tag], $newIndent))->string;
        }
        foreach ($this->tabs as $item) {
            yield "$newIndent<TAB $item />";
        }
        foreach ($this->buttons as $item) {
            yield "$newIndent<BTN $item />";
        }
        yield "$indent</{$tag}>";
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

            // new
            if ($item instanceof static) {
                $id && $item->id = $id;
                $class && $item->class = $class;
                yield $item;
                continue;
            }

            // merge
            if ($id) {
                $item = new static(null, is_iterable($item) ? static::create($item) : []);
                $item->id     = $id;
                $item->merged = true;
                yield $item;
                continue;
            }

            // tabs & buttons
            yield $key => $item;
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return fn\map($this->toString('OXMENU'))->string;
    }

    /**
     * @param string $lang
     * @return Generator
     */
    public function translate(string $lang): Generator
    {
        if (!$this->merged) {
            yield $this->getId() => $this->getLabel($lang);
        }
        foreach ($this->tabs as $item) {
            yield $item->getId() => $item->getLabel($lang);
        }
        foreach ($this->buttons as $item) {
            yield $item->getId() => $item->getLabel($lang);
        }
    }
}
