<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Meta;

use Generator;
use Reflector;

/**
 * @property-read ReflectionConstant[] $constants
 * @property-read string               $shortName
 * @property-read string[]             $docBlock
 * @property-read string[]             $use
 */
class ReflectionNamespace implements Reflector
{
    use ReflectionTrait;

    protected const DEFAULT = ['constants' => [], 'docBlock' => [], 'use' => []];

    public function relative($fqn): string
    {
        return strrpos($fqn, $this->name) === 0 ? substr($fqn, strlen($this->name)) : $fqn;
    }

    /**
     * @see $name
     * @return string
     */
    protected function resolveName(): string
    {
        $name = $this->properties['name'] ?? null;
        return substr($name, -1) === '\\' ? $name : $name . '\\';
    }

    /**
     * @see $shortName
     * @return string
     */
    protected function resolveShortName(): string
    {
        $parts = array_filter(explode('\\', $this->name));
        return end($parts) . '\\';
    }

    /**
     * @param array $args
     * @return self[]
     */
    public static function all(...$args): iterable
    {
        return self::cached(...$args)->sort(static function(self $left, self $right) {
            return (count($left->use) - count($right->use)) ?: strcmp($left, $right);
        });
    }

    public function toPhp(): Generator
    {
        yield '/**';
        foreach ($this->docBlock as $line) {
            $line = trim($line);
            yield $line ? " * $line" : ' *';
        }
        yield ' */';
        yield 'namespace ' . substr($this->name, 0, -1);
        yield '{';
        foreach ($this->use as $use) {
            yield "    use $use;";
        }
        foreach ($this->constants as $const) {
            yield '';
            yield from $const->toPhp();
        }
        yield '}';
    }
}
