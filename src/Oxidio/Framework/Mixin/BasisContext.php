<?php declare(strict_types=1);
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Framework\Mixin;

use OxidEsales\EshopCommunity\Internal\Transition\Utility;

trait BasisContext
{
    /**
     * @var Utility\ContextInterface|Utility\BasicContextInterface
     */
    protected $contextProxy;

    public function getContainerCacheFilePath(): string
    {
        return $this->contextProxy->getContainerCacheFilePath();
    }

    public function getGeneratedServicesFilePath(): string
    {
        return $this->contextProxy->getGeneratedServicesFilePath();
    }

    public function getConfigurableServicesFilePath(): string
    {
        return $this->contextProxy->getConfigurableServicesFilePath();
    }

    public function getSourcePath(): string
    {
        return $this->contextProxy->getSourcePath();
    }

    public function getModulesPath(): string
    {
        return $this->contextProxy->getModulesPath();
    }

    public function getEdition(): string
    {
        return $this->contextProxy->getEdition();
    }

    public function getCommunityEditionSourcePath(): string
    {
        return $this->contextProxy->getCommunityEditionSourcePath();
    }

    public function getProfessionalEditionRootPath(): string
    {
        return $this->contextProxy->getProfessionalEditionRootPath();
    }

    public function getEnterpriseEditionRootPath(): string
    {
        return $this->contextProxy->getEnterpriseEditionRootPath();
    }

    public function getDefaultShopId(): int
    {
        return $this->contextProxy->getDefaultShopId();
    }

    public function getAllShopIds(): array
    {
        return $this->contextProxy->getAllShopIds();
    }

    public function getBackwardsCompatibilityClassMap(): array
    {
        return $this->contextProxy->getBackwardsCompatibilityClassMap();
    }

    public function getProjectConfigurationDirectory(): string
    {
        return $this->contextProxy->getProjectConfigurationDirectory();
    }

    public function getConfigurationDirectoryPath(): string
    {
        return $this->contextProxy->getConfigurationDirectoryPath();
    }

    public function getShopRootPath(): string
    {
        return $this->contextProxy->getShopRootPath();
    }

    public function getConfigFilePath(): string
    {
        return $this->contextProxy->getConfigFilePath();
    }

    public function getConfigTableName(): string
    {
        return $this->contextProxy->getConfigTableName();
    }
}
