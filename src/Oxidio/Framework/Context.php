<?php declare(strict_types=1);
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Framework;

use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;


class Context implements ContextInterface
{
    use Mixin\Context;

    public function __construct(ContextInterface $contextProxy)
    {
        $this->contextProxy = $contextProxy;
    }

    public function getShopsConfigurationPath(string ...$path): string
    {
        return $this->getProjectConfigurationDirectory() . 'shops/' . implode('/', $path);
    }

    public function getEnvironmentConfigurationPath(string ...$path): string
    {
        return $this->getProjectConfigurationDirectory() . 'environment/' . implode('/', $path);
    }
}
