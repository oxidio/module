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
     * @var Provider
     */
    protected $provider;

    protected function init(): void
    {
    }

    public function __construct(Provider $provider, iterable $properties = [])
    {
        $this->provider = $provider;
        $this->propsInit($properties);
        $this->init();
    }

    /**
     * @see $name
     * @return string
     */
    protected function resolveName(): string
    {
        return (string)($this->properties['name'] ?? null);
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
     * @inheritdoc
     */
    public function __toString()
    {
        return $this->name;
    }
}
