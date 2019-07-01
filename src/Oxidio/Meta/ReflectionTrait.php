<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Meta;

use fn;
use Generator;

/**
 * @property-read string $name
 */
trait ReflectionTrait
{
    use fn\PropertiesTrait\ReadOnly;
    use fn\PropertiesTrait\Init;

    /**
     * @inheritDoc
     */
    protected function propertyGenerate(string $property, $value): Generator
    {
        yield $property => $value instanceof Generator ? fn\traverse($value) : $value;
    }

    /**
     * @var self[]
     */
    private static $cache = [];

    /**
     * @inheritdoc
     */
    public function __construct(array $properties = [])
    {
        $this->initProperties($properties);
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
     * @param string $name
     * @param array $properties
     *
     * @return static
     */
    public static function get(string $name, array $properties = []): self
    {
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
