<?php declare(strict_types=1);
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Framework\Configuration;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use JsonSerializable;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use Php;

/**
 * @property-read string[] $ids
 * @property-read int $shopId
 */
class Modules implements IteratorAggregate, ArrayAccess, JsonSerializable
{
    /**
     * @uses resolveIds
     */
    use Php\PropertiesTrait\ReadOnly;
    use Php\ArrayAccessTrait;

    private $shops;

    public function __construct(Shops $shops, int $shopId)
    {
        $this->shops = $shops;
        $this->properties = ['shopId' => $shopId];
    }

    /**
     * @return ModuleConfiguration[]|ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->shops->get($this->shopId)->getModuleConfigurations());
    }

    public function jsonSerialize()
    {
        return $this->data();
    }

    /**
     * @return array
     */
    private function data(): array
    {
        return is_array($this->data) ? $this->data : $this->data = $this->shops->data($this->shopId)['modules'] ?? [];
    }

    protected function resolveIds(): array
    {
        return $this->shops->get($this->shopId)->getModuleIdsOfModuleConfigurations();
    }

    public function get(string $moduleId): ModuleConfiguration
    {
        return $this->shops->get($this->shopId)->getModuleConfiguration($moduleId);
    }

    public function has(string $moduleId): bool
    {
        return $this->shops->get($this->shopId)->hasModuleConfiguration($moduleId);
    }
}
