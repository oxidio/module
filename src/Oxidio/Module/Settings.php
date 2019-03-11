<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Module;

use fn;
use Generator;
use IteratorAggregate;

/**
 */
class Settings implements IteratorAggregate
{
    /**
     * @var iterable
     */
    private $groups;

    /**
     * @param iterable $groups
     */
    public function __construct($groups)
    {
        $this->groups = $groups;
    }

    /**
     * @inheritdoc
     */
    public function getIterator(): Generator
    {
        foreach ($this->groups as $groupLabel => $group) {
            foreach ($group as $name => $setting) {
                if (empty($setting['type'])) {
                    $setting = fn\merge($setting, $this->type($setting));
                }
                yield fn\merge(['group' => $groupLabel, 'name' => $name], $setting);
            }
        }
    }

    /**
     * @param string $lang
     *
     * @return \Generator
     */
    public function translate(string $lang): Generator
    {
        yield 'charset' => 'UTF-8';
        foreach ($this->groups as $groupLabel => $group) {
            yield 'SHOP_MODULE_GROUP_' . $groupLabel => $groupLabel;
            foreach ($group as $name => $setting) {
                $label = $setting['label'] ?? $name;
                $key   = 'SHOP_MODULE_' . $name;

                yield $key => is_array($label) ? $label[$lang] ?? current($label) : $label;
                if ($help = $setting['?'] ?? []) {
                    yield 'HELP_' . $key => is_array($help) ? $help[$lang] ?? current($help) : $help;
                }
                if (!isset($setting['selected'])) {
                    continue;
                }
                foreach ($this->constraints($setting['value'] ?? []) as $value => $label) {
                    yield "{$key}_{$value}" => is_array($label) ? $label[$lang] ?? current($label) : $label;
                }
            }
        }
    }

    /**
     * @param array $setting
     *
     * @return array
     */
    private function type(array $setting): array
    {
        $value = $setting['value'] ?? null;

        if (is_bool($value)) {
            return ['type' => 'bool', 'value' => $value ? 'true' : 'false'];
        }
        if (!is_iterable($value)) {
            return ['type' => 'str', 'value' => $value];
        }

        if ($selected = $setting['selected'] ?? null) {
            return [
                'type'        => 'select',
                'value'       => $selected,
                'constraints' => $this->constraints($value)->keys()->string('|')
            ];
        }

        return ['type' => 'aarr', 'value' => $value];
    }

    /**
     * @param iterable $value
     *
     * @return fn\Map
     */
    private function constraints($value): fn\Map
    {
        return fn\map(is_iterable($value) ? $value : [], function($value, &$key) {
            if (is_numeric($key)) {
                $key = $value;
            }
            return $value;
        });
    }
}
