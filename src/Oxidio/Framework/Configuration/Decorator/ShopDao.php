<?php declare(strict_types=1);
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Framework\Configuration\Decorator;

use Closure;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataMapper\ShopConfigurationDataMapperInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataMapper\ModuleConfiguration\ModuleSettingsDataMapper;
use Php;

class ShopDao implements ShopConfigurationDaoInterface
{
    private $proxy;
    private $mapper;
    private $settings;
    private $cache = [];

    public function __construct(
        ShopConfigurationDaoInterface $proxy,
        ShopConfigurationDataMapperInterface $mapper,
        iterable $settings
    ) {
        $this->proxy = $proxy;
        $this->mapper = $mapper;
        $this->settings = $settings;
    }

    private function create(int $shopId): ShopConfiguration
    {
        $config = $this->proxy->get($shopId);
        $data = $this->mapper->toData($config);
        foreach ($this->settings as $id => $values) {
            if (!is_array($module = $data['modules'][$id] ?? null)) {
                continue;
            }
            if ($values instanceof Closure) {
                $data['modules'][$id] = Php::arr($values($module, $shopId, $id));
            } else {
                foreach ($values as $name => $value) {
                    $data['modules'][$id][ModuleSettingsDataMapper::MAPPING_KEY][$name]['value'] = $value;
                }
            }
        }
        return $this->mapper->fromData($data);
    }

    public function get(int $shopId): ShopConfiguration
    {
        return $this->cache[$shopId] ?? $this->cache[$shopId] = $this->create($shopId);
    }

    public function save(ShopConfiguration $shopConfiguration, int $shopId): void
    {
        unset($this->cache[$shopId]);
        call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    public function getAll(): array
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    public function deleteAll(): void
    {
        $this->cache = [];
        call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }
}
