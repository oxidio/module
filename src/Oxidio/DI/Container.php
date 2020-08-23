<?php declare(strict_types=1);
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\DI;

use Exception;
use Invoker\ParameterResolver\AssociativeArrayResolver;
use Invoker\ParameterResolver\Container\ParameterNameContainerResolver;
use Invoker\ParameterResolver\DefaultValueResolver;
use OxidEsales\EshopCommunity\Internal\Container\BootstrapContainerFactory;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use Php;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

class Container implements Php\DI\MutableContainerInterface
{
    use Php\DI\AwareTrait;
    use Php\DI\CallerTrait;

    /**
     * @var self
     */
    protected static $instance;

    /**
     * @var ContainerInterface
     */
    private $proxy;

    public function __construct(ContainerInterface $proxy)
    {
        $this->proxy = $proxy;
        $this->set(static::class, $this);
        $this->set(self::class, $this);
    }

    public function get($id)
    {
        return $this->proxy->get($id);
    }

    public function has($id): bool
    {
        return $this->proxy->has($id);
    }

    public function set(string $id, $value): void
    {
        if ($this->proxy instanceof Php\DI\MutableContainerInterface) {
            $this->proxy->set($id, $value);
            return;
        }
        throw new class(
            Php::str('missing %s', Php\DI\MutableContainerInterface::class)
        ) extends Exception implements ContainerExceptionInterface{};
    }

    public static function oe(): ContainerInterface
    {
        try {
            return ContainerFactory::getInstance()->getContainer();
        } catch (Exception $e) {
            return BootstrapContainerFactory::getBootstrapContainer();
        }
    }

    public static function invoker(ContainerInterface $container): Php\DI\Invoker
    {
        return new Php\DI\Invoker(
            $container->get(SmartyTemplateVars::class),
            new AssociativeArrayResolver(),
            $container,
            new ParameterNameContainerResolver($container),
            $container->get(RegistryResolver::class),
            new DefaultValueResolver()
        );
    }

    public static function instance(): self
    {
        if (!self::$instance) {
            $oe = static::oe();
            $rr = $oe->get(RegistryResolver::class);
            $di = Php::di($oe, Php\Composer\DIClassLoader::instance()->getContainer(), ...[$rr->container, [
                Php\DI\Invoker::class => function (ContainerInterface $container) {
                    return self::invoker($container);
                },
            ]]);
            self::$instance = new self($di);
        }
        return self::$instance;
    }
}
