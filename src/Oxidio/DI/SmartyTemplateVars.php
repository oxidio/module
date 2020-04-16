<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\DI;

use ArrayAccess;
use Invoker\ParameterResolver\AssociativeArrayResolver;
use Invoker\ParameterResolver\Container\ParameterNameContainerResolver;
use Invoker\ParameterResolver\DefaultValueResolver;
use Php;
use Generator;
use Invoker\ParameterResolver\TypeHintVariadicResolver;
use IteratorAggregate;
use OxidEsales\Eshop\Core\Registry;
use Psr\Container\ContainerInterface;
use ReflectionParameter;
use Smarty;
use DI;

/**
 * @property-read Smarty $smarty
 */
class SmartyTemplateVars implements ArrayAccess, IteratorAggregate
{
    use Php\PropertiesTrait\ReadOnly;

    /**
     * @param Smarty|null $smarty
     */
    public function __construct(Smarty $smarty = null)
    {
        $this->properties['smarty'] = $smarty ?: Registry::getUtilsView()->getSmarty();
    }

    public static function container(...$args): ContainerInterface
    {
        $rr = new RegistryResolver();
        return Php::di(...$args, ...[$rr->container, [
            RegistryResolver::class => $rr,
            self::class => DI\create(),
            Smarty::class => function (self $stv) {
                return $stv->smarty;
            },
            Php\DI\Invoker::class => function (ContainerInterface $c, self $stv, RegistryResolver $rr) {
                return new Php\DI\Invoker(
                    $stv,
                    new AssociativeArrayResolver(),
                    $c,
                    new ParameterNameContainerResolver($c),
                    $rr,
                    new DefaultValueResolver()
                );
            },
        ]]);
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

    /**
     * @inheritdoc
     */
    public function getIterator(): Php\Map
    {
        return Php::map($this->smarty->get_template_vars());
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset): bool
    {
        return isset($this->smarty->get_template_vars()[$offset]);
    }

    /**
     * @inheritdoc
     */
    public function offsetGet($offset)
    {
        return $this->smarty->get_template_vars()[$offset];
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value): void
    {
        Php::fail($offset);
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset): void
    {
        Php::fail($offset);
    }
}
