<?php declare(strict_types=1);
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\DI;

use Exception;
use Php;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

class Container implements Php\DI\MutableContainerInterface
{
    use Php\DI\AwareTrait;
    use Php\DI\CallerTrait;

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
}
