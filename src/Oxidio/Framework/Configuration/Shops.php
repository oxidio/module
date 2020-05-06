<?php declare(strict_types=1);
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Framework\Configuration;

use Oxidio\Framework\Context;
use Symfony\Component\Config\Definition\NodeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataMapper\ShopConfigurationDataMapperInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Storage\FileStorageFactoryInterface;
use Php;

/**
 * @property-read Modules $modules
 * @property-read array $data
 */
class Shops
{
    private $context;
    private $factory;
    private $mapper;
    private $node;

    /**
     * @see resolveModules, resolveData
     */
    use Php\PropertiesTrait\ReadOnly;

    public function __construct(
        Context $context,
        FileStorageFactoryInterface $factory,
        ShopConfigurationDataMapperInterface $mapper,
        NodeInterface $node
    ) {
        $this->context = $context;
        $this->factory = $factory;
        $this->mapper = $mapper;
        $this->node = $node;
    }

    public function data(int $shopId): array
    {
        $storage = $this->factory->create($this->context->getShopsConfigurationPath("$shopId.yaml"));
        return $this->node->normalize($storage->get());
    }

    protected function resolveData(): array
    {
        return $this->data($this->context->getCurrentShopId());
    }

    public function get(int $shopId): ShopConfiguration
    {
        return $this->mapper->fromData($this->data($shopId));
    }

    public function modules(int $shopId): Modules
    {
        return new Modules($this, $shopId);
    }

    protected function resolveModules(): Modules
    {
        return $this->modules($this->context->getCurrentShopId());
    }
}
