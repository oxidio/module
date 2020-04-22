<?php declare(strict_types=1);
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Cli\Shop;


use Generator;
use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\Service\ShopStateServiceInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ShopConfigurationDaoBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ProjectConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use Php;

class Info
{
    /**
     * Show shop info (context, state, modules)
     *
     * @param ContextInterface                    $context
     * @param ProjectConfigurationDaoInterface    $pr
     * @param ShopStateServiceInterface           $state
     * @param ShopConfigurationDaoBridgeInterface $shopBridge
     * @return Generator
     */
    public function __invoke(
        ContextInterface $context,
        ProjectConfigurationDaoInterface $pr,
        ShopStateServiceInterface $state,
        ShopConfigurationDaoBridgeInterface $shopBridge
    ): Generator {
        yield Php::str('<fg=cyan;options=bold># %s</>', BasicContextInterface::class);
        yield from self::methods(
            $context,
            'getContainerCacheFilePath',
            'getGeneratedServicesFilePath',
            'getConfigurableServicesFilePath',
            'getSourcePath',
            'getModulesPath',
            'getEdition',
            'getCommunityEditionSourcePath',
            'getProfessionalEditionRootPath',
            'getDefaultShopId',
            'getAllShopIds',
//            'getBackwardsCompatibilityClassMap',
            'getProjectConfigurationDirectory',
            'getConfigurationDirectoryPath',
            'getShopRootPath',
            'getConfigFilePath',
            'getConfigTableName'
        );
        yield '';

        yield Php::str('<fg=cyan;options=bold># %s</>', ContextInterface::class);
        yield from self::methods(
            $context,
            'getCurrentShopId',
            'getLogLevel',
            'getRequiredContactFormFields',
            'getConfigurationEncryptionKey',
            'isEnabledAdminQueryLog',
            'isAdmin',
            'getAdminLogFilePath',
            'getSkipLogTags'
//            'getAdminUserId'
        );
        yield '';

        yield Php::str('<fg=cyan;options=bold># %s</>', ProjectConfigurationDaoInterface::class);
        yield from self::methods($pr, 'isConfigurationEmpty');
        yield '';

        yield Php::str('<fg=cyan;options=bold># %s</>', ShopStateServiceInterface::class);
        yield from self::methods($state, 'isLaunched');
        yield '';

        yield Php::str('<fg=cyan;options=bold># modules</>');
        yield from $this->modules($shopBridge);
    }

    private function modules(ShopConfigurationDaoBridgeInterface $shop): Generator {
        foreach ($shop->get()->getModuleConfigurations() as $id => $module) {
            yield Php::str('<fg=yellow>## %s</>', $id);
            yield from self::methods(
                $module,
                'getPath',
                'getVersion',
                'getTitle',
                'getDescription',
                'getLang',
                'getThumbnail',
                'isConfigured',
                'getAuthor',
                'getUrl',
                'getEmail',
                'hasClassExtensions',
                'hasTemplateBlocks',
                'hasTemplates',
                'hasControllers',
                'hasSmartyPluginDirectories',
                'hasEvents',
                'hasClassWithoutNamespaces',
                'hasModuleSettings'
            );
            yield '';
        }
    }

    private static function methods($obj, string ...$methods): Generator
    {
        foreach ($methods as $method) {
            $values = Php::arr(is_array($value = $obj->$method()) ? $value : [$value], function ($value) {
                $fg = 'white';
                if (is_bool($value)) {
                    $fg = $value ? 'green' : 'red';
                    $value = json_encode($value);
                } else if (is_array($value)) {
                    $value = new Php\ArrayExport($value);
                }
                yield "<fg=$fg>$value</>";
            });
            $class = str_replace('OxidEsales\EshopCommunity\Internal\\', '', get_class($obj));
            yield Php::str(
                '<fg=magenta>%s</>-><fg=yellow>%s :</> %s',
                $class,
                $method,
                implode(',', $values)
            );
        }
    }
}
