<?php declare(strict_types=1);
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\DI;

use Php;
use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
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
    }

    public function get($id)
    {
        return $this->proxy->get($id);
    }

    public function has($id): bool
    {
        return $this->proxy->has($id);
    }
}
