<?php declare(strict_types=1);
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\DI;

use Exception;
use OxidEsales\EshopCommunity\Internal\Container\BootstrapContainerFactory;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use Php;
use Psr\Container\ContainerInterface;
use OxidEsales\Eshop\Core;

class Container implements Php\DI\MutableContainerInterface
{
    use Php\DI\AwareTrait;
    use Php\DI\CallerTrait;

    private $proxy;

    public function __construct(Php\DI\MutableContainerInterface $proxy)
    {
        $this->proxy = $proxy;
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
        $this->proxy->set($id, $value);
    }

    public static function mutable(iterable $definitions = null): Php\DI\MutableContainerInterface
    {
        return Php::di(...Php::arr($definitions ?: []));
    }

    public static function oe(): ContainerInterface
    {
        try {
            return ContainerFactory::getInstance()->getContainer();
        } catch (Exception $e) {
            return BootstrapContainerFactory::getBootstrapContainer();
        }
    }
}

