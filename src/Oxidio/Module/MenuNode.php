<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Module;

use Php;

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
        return $this->id ?: $this->id = $this->getLabel();
    }

    /**
     * @param string|null $lang
     * @return string
     */
    public function getLabel($lang = ''): ?string
    {
        if (is_string($this->label)) {
            return $this->label;
        }
        return $this->label[$lang] ?? reset($this->label);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return Php::map([
            'id' => $this->getId(),
            'cl' => $this->class,
            'clparam' => http_build_query($this->params, '', '&amp;'),
            'list' => $this->list,
            'groups' => implode(',', $this->groups), // nogroups?
            'rights' => implode(',', $this->rights), // norights?
        ], function ($value, $key) {
            return $value ? "$key=\"$value\"" : null;
        })->string(' ');
    }
}
