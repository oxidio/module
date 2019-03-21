<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Module;

use fn;

/**
 */
class MenuNode
{
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
     * @var string[]
     */
    public $rights = [];

    /**
     * @var string[]
     */
    public $groups = [];

    /**
     * @var array
     */
    public $params = [];

    /**
     * @param string|string[] $props
     */
    public function __construct($props)
    {
        $this->class  = $props['class'] ?? null;
        $this->list   = $props['list'] ?? null;
        $this->rights = $props['rights'] ?? [];
        $this->groups = $props['groups'] ?? [];
        $this->params = $props['params'] ?? [];
        if (is_array($props)) {
            unset($props['list'], $props['rights'], $props['groups'], $props['params'], $props['class']);
        }
        $this->label = $props['label'] ?? $props;
    }

    public function getId(): string
    {
        if (!$this->id) {
            $this->id = is_string($this->label) ? $this->label : reset($this->label);
        }
        return $this->id;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return fn\map([
            'id'       => $this->getId(),
            'cl'       => $this->class,
            'clparam'  => http_build_query($this->params, '', '&amp;'),
            'list'     => $this->list,
            'groups'   => implode(',', $this->groups), // nogroups?
            'rights'   => implode(',', $this->rights), // norights?
        ], function($value, $key) {
            return $value ? "$key=\"$value\"" : null;
        })->string(' ');
    }
}
