<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Module;

use Php;
use Generator;
use IteratorAggregate;
use JsonSerializable;

/**
 */
class Settings implements IteratorAggregate, JsonSerializable
{
    /**
     * @var iterable
     */
    private $groups;

    /**
     * @param iterable $groups
     */
    public function __construct(iterable $groups)
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
                if (empty($setting[Settings\TYPE])) {
                    $setting = Php\merge($setting, $this->type($setting));
                }
                unset($setting[Settings\LABEL], $setting[Settings\HELP]);
                yield Php\merge([Settings\GROUP => $groupLabel, Settings\NAME => $name], $setting);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return Php\traverse($this);
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
                $label = $setting[Settings\LABEL] ?? $name;
                $key   = 'SHOP_MODULE_' . $name;

                yield $key => is_array($label) ? $label[$lang] ?? current($label) : $label;
                if ($help = $setting[Settings\HELP] ?? []) {
                    yield 'HELP_' . $key => is_array($help) ? $help[$lang] ?? current($help) : $help;
                }
                if (!isset($setting[Settings\SELECTED])) {
                    continue;
                }
                foreach ($this->constraints($setting[Settings\VALUE] ?? []) as $value => $label) {
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
        $value = $setting[Settings\VALUE] ?? null;

        if (is_bool($value)) {
            return [Settings\TYPE => 'bool', Settings\VALUE => $value ? 'true' : 'false'];
        }
        if (!is_iterable($value)) {
            return [Settings\TYPE => 'str', Settings\VALUE => $value];
        }

        if ($selected = $setting[Settings\SELECTED] ?? null) {
            return [
                Settings\TYPE        => 'select',
                Settings\VALUE       => $selected,
                'constraints' => $this->constraints($value)->keys()->string('|')
            ];
        }

        return [Settings\TYPE => 'aarr', Settings\VALUE => $value];
    }

    /**
     * @param iterable $value
     *
     * @return Php\Map
     */
    private function constraints($value): Php\Map
    {
        return Php\map(is_iterable($value) ? $value : [], function($value, &$key) {
            if (is_numeric($key)) {
                $key = $value;
            }
            return $value;
        });
    }
}
