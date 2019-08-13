<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Meta;

use Generator;
use php;

/**
 * @property-read ReflectionNamespace $namespace
 * @property-read string[]            $docBlock
 * @property-read string              $shortName
 * @property-read string              $value
 */
class ReflectionConstant
{
    use ReflectionTrait;

    protected const DEFAULT = ['docBlock' => [], 'value' => null];

    public function setValue($value, $export = false): self
    {
        $this->properties['value'] = $export ? var_export($value, true) : $value;
        return $this;
    }

    /**
     * @see $namespace
     * @return ReflectionNamespace
     */
    protected function resolveNamespace(): ReflectionNamespace
    {
        $name = $this->properties['name'] ?? null;
        $last = strrpos($name, '\\');
        $last = substr($name, 0, $last);
        return $this->provider->ns($last)->add('constants', $this);
    }

    /**
     * @see $name
     * @return string
     */
    protected function resolveName(): string
    {
        $name = $this->properties['name'] ?? null;
        $isReserved = php\hasValue(strtolower($this->namespace->relative($name)), php\Composer\DIPackages::RESERVED);
        return $isReserved ? $name . '_' : $name;
    }

    /**
     * @see $shortName
     * @return string
     */
    protected function resolveShortName(): string
    {
        return $this->namespace->relative($this->name);
    }

    public function toPhp(): Generator
    {
        yield '    /**';
        foreach ($this->docBlock as $line) {
            $line = trim($line);
            yield $line ? "     * $line" : '     *';
        }
        yield '     */';
        yield "    const {$this->shortName} = {$this->value};";
    }
}
