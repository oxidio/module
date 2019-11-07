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
    public const TYPE = 'type';
    public const NAME = 'name';
    public const GROUP = 'group';
    public const VALUE = 'value';
    public const LABEL = 'label';
    public const SELECTED = 'selected';
    public const HELP = '?';

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
                if (empty($setting[self::TYPE])) {
                    $setting = Php::merge($setting, $this->type($setting));
                }
                unset($setting[self::LABEL], $setting[self::HELP]);
                yield Php::merge([self::GROUP => $groupLabel, self::NAME => $name], $setting);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return Php::traverse($this);
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
                $label = $setting[self::LABEL] ?? $name;
                $key   = 'SHOP_MODULE_' . $name;

                yield $key => is_array($label) ? $label[$lang] ?? current($label) : $label;
                if ($help = $setting[self::HELP] ?? []) {
                    yield 'HELP_' . $key => is_array($help) ? $help[$lang] ?? current($help) : $help;
                }
                if (!isset($setting[self::SELECTED])) {
                    continue;
                }
                foreach ($this->constraints($setting[self::VALUE] ?? []) as $value => $label) {
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
        $value = $setting[self::VALUE] ?? null;

        if (is_bool($value)) {
            return [self::TYPE => 'bool', self::VALUE => $value ? 'true' : 'false'];
        }
        if (!is_iterable($value)) {
            return [self::TYPE => 'str', self::VALUE => $value];
        }

        if ($selected = $setting[self::SELECTED] ?? null) {
            return [
                self::TYPE => 'select',
                self::VALUE => $selected,
                'constraints' => $this->constraints($value)->keys()->string('|')
            ];
        }

        return [self::TYPE => 'aarr', self::VALUE => $value];
    }

    /**
     * @param iterable $value
     *
     * @return Php\Map
     */
    private function constraints($value): Php\Map
    {
        return Php::map(is_iterable($value) ? $value : [], function ($value, &$key) {
            if (is_numeric($key)) {
                $key = $value;
            }
            return $value;
        });
    }
}
