<?php declare(strict_types=1);
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Framework\Configuration;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopEnvironmentConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataMapper\ModuleConfiguration\ModuleSettingsDataMapper;

class ShopEnvironmentDaoDecorator implements ShopEnvironmentConfigurationDaoInterface
{
    private $proxy;

    private $settings;

    public function __construct(ShopEnvironmentConfigurationDaoInterface $proxy, iterable $settings)
    {
        $this->proxy = $proxy;
        $this->settings = $settings;
    }

    public function get(int $shopId): array
    {
        $data = $this->proxy->get($shopId);
        foreach ($this->settings as $module => $values) {
            foreach ($values as $name => $value) {
                $data['modules'][$module][ModuleSettingsDataMapper::MAPPING_KEY][$name]['value'] = $value;
            }
        }
        return $data;
    }

    public function remove(int $shopId): void
    {
        $this->proxy->remove($shopId);
    }
}
