<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Meta;

use fn;

/**
 * @property-read string $name
 */
trait ReflectionTrait
{
    use fn\PropertiesTrait\ReadOnly;
    use fn\PropertiesTrait\Init;

    /**
     * @var self[]
     */
    private static $cache = [];

    /**
     * @see $name
     * @return string
     */
    protected function resolveName(): string
    {
        return (string)($this->properties['name'] ?? null);
    }

    /**
     * @param array $args
     * @return fn\Map|self[]
     */
    public static function cached(...$args): fn\Map
    {
        return fn\map(self::$cache, ...$args);
    }

    public function add(string $property, ...$lines): self
    {
        $this->__get($property);
        foreach ($lines as $line) {
            if (!$line || !fn\hasValue($line, $this->$property)) {
                $this->properties[$property][] = $line;
            }
        }
        return $this;
    }

    /**
     * @param string|string[] $name
     * @param array $properties
     *
     * @return static
     */
    public static function get($name, array $properties = []): self
    {
        is_iterable($name) && $name = fn\map($name, static function($part) {
            return trim($part, '\\') ?: null;
        })->string('\\');
        return self::$cache[$name] ?? self::$cache[$name] = self::create($name, $properties);
    }

    public static function create(string $name, array $properties = []): self
    {
        return new static(array_merge($properties, ['name' => $name]));
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public static function export(): void
    {
    }
}
