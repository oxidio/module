<?php declare(strict_types=1);
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\DI;

use ArrayAccess;
use Php;
use Generator;
use Invoker\ParameterResolver\TypeHintVariadicResolver;
use ReflectionParameter;
use Smarty;

class SmartyTemplateVars implements ArrayAccess
{
    private $smarty;

    public function __construct(Smarty $smarty)
    {
        $this->smarty = $smarty;
    }

    /**
     * @see GeneratorResolver
     * @param ReflectionParameter $parameter
     *
     * @return Generator
     */
    public function __invoke(ReflectionParameter $parameter): Generator
    {
        if ($parameter->isVariadic()) {
            yield from (new TypeHintVariadicResolver)($parameter, $this->smarty->get_template_vars());
        } else if ($class = $parameter->getClass()) {
            switch ($class->getName()) {
                case Smarty::class: yield $this->smarty; break;
                case static::class: yield $this; break;
                default:
                    foreach ($this->smarty->get_template_vars() as $var) {
                        if (is_object($var) && $class->isInstance($var)) {
                            yield $var;
                            break;
                        }
                    }
            }
        }
    }

    public function offsetExists($offset): bool
    {
        return isset($this->smarty->get_template_vars()[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->smarty->get_template_vars()[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        Php::fail($offset);
    }

    public function offsetUnset($offset): void
    {
        Php::fail($offset);
    }
}
