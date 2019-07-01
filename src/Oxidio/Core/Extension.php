<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core;

use fn;
use JsonSerializable;
use OxidEsales\Eshop\Core\Database\TABLE;

/**
 * @property string $status
 * @property-read string $id
 * @property-read string $type
 * @property-read string $version
 * @property-read string $path
 * @property-read string[] $controllers
 * @property-read array[] $events
 * @property-read string[] $classes aModuleFiles|aModules|aModuleExtensions ['cl', 'ox-cl' => 'cl']
 * @property-read array $templates
 * @property-read array $files
 * @property-read array $config
 *
 */
class Extension implements JsonSerializable
{
    use fn\PropertiesTrait;
    use fn\PropertiesTrait\Init;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_REMOVED = 'removed';

    public const CONFIG_KEYS = [
        'aModuleEvents' => 'events',
        'aModuleFiles' => 'files',
        'aModulePaths' => 'path',
        'aModuleTemplates' => 'templates',
        'aModuleControllers' => 'controllers',
        'aModuleVersions' => 'version',
    ];

    protected const DEFAULT = [
        'id' => '',
        'type' => self::MODULE,
        'version' => null,
        'path' => null,
        'status' => self::STATUS_ACTIVE,
        'config' => [],
        'templates' => [],
        'controllers' => [],
        'events' => [],
        'files' => [],
    ];

    public const SHOP = '';
    public const MODULE = 'module';
    public const THEME = 'theme';

    /**
     * @var Shop
     */
    protected $shop;

    /**
     * @param Shop $shop
     * @param iterable $data
     */
    public function __construct(Shop $shop, $data)
    {
        $this->shop = $shop;
        $this->initProperties($data);
    }

    /**
     * @param Shop $shop
     *
     * @return array
     */
    protected static function shopData(Shop $shop): array
    {
        $from = fn\str('(SELECT ' .
            fn\map([
                '{c.shop} shop',
                '{c.mod} module',
                '{c.var} name',
                '{c.type} type',
                "DECODE({c.val}, '{pass}') value",
                '{cd.gr} gr',
                '{cd.pos} pos',
            ])->string(', ') .
            ' FROM {c} LEFT JOIN {cd} ON {c.mod} = {cd.mod} AND {c.var} = {cd.var}) config',
            [
                'pass' => $shop->configKey,
                'c' => TABLE\OXCONFIG . ' c',
                'cd' => TABLE\OXCONFIGDISPLAY . ' cd',
                'c.mod' => 'c.' . TABLE\OXCONFIG\OXMODULE,
                'c.shop' => 'c.' . TABLE\OXCONFIG\OXSHOPID,
                'c.var' => 'c.' . TABLE\OXCONFIG\OXVARNAME,
                'c.val' => 'c.' . TABLE\OXCONFIG\OXVARVALUE,
                'c.type' => 'c.' . TABLE\OXCONFIG\OXVARTYPE,
                'cd.mod' => 'cd.' . TABLE\OXCONFIGDISPLAY\OXCFGMODULE,
                'cd.var' => 'cd.' . TABLE\OXCONFIGDISPLAY\OXCFGVARNAME,
                'cd.gr' => 'cd.' . TABLE\OXCONFIGDISPLAY\OXGROUPING,
                'cd.pos' => 'cd.' . TABLE\OXCONFIGDISPLAY\OXPOS,
            ]
        );

        $query = $shop->query($from, ['shop' => $shop->id])->orderBy('module', 'gr', 'pos', 'name');
        return fn\traverse(fn\map($query,
            static function (array $row) {
                ['module' => $module, 'value' => $value, 'name' => $name] = $row;
                strpos($row['type'], 'rr') && $value = unserialize($value, [null]);
                if ($module && strpos($module, ':') === false) {
                    $module = static::MODULE . ':' . $module;
                }
                return fn\mapGroup($module)->andKey($name)->andValue($value);
            }
        ), static function (array $config, $module) {
            [$type, $module] = explode(':', $module);
            return fn\mapKey((string)$module)->andValue([
                'config' => $config,
                'type' => (string)$type,
            ]);
        });
    }

    /**
     * @param Shop $shop
     * @return fn\Map|static[]
     */
    public static function all(Shop $shop): fn\Map
    {
        $data = static::shopData($shop);
        $conf = $data[self::SHOP]['config'] ?? [];

        $attr = function (array $data, $attr): array {
            return fn\traverse($data, static function ($value, $module) use ($attr) {
                return fn\mapKey($module)->andValue([$attr => $value]);
            });
        };

        $data = array_merge_recursive(
            $data,
            $attr($conf['aModuleEvents'] ?? [], 'events'),
            $attr($conf['aModuleFiles'] ?? [], 'files'),
            $attr($conf['aModulePaths'] ?? [], 'path'),
            $attr($conf['aModuleTemplates'] ?? [], 'templates'),
            $attr($conf['aModuleControllers'] ?? [], 'controllers'),
            $attr($conf['aModuleVersions'] ?? [], 'version'),
            array_fill_keys($conf['aDisabledModules'] ?? [], ['status' => static::STATUS_INACTIVE])
        );

        unset($data[self::SHOP]['config']['aDisabledModules']);
        foreach (fn\keys(self::CONFIG_KEYS) as $key) {
            unset($data[self::SHOP]['config'][$key]);
        }

        return fn\map($data, static function (array $ext, $id) use ($shop) {
            return new static($shop, $ext + ['id' => $id]);
        });
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return $this->properties;
    }

    /**
     * @see $status
     * @see \OxidEsales\EshopCommunity\Core\Module\ModuleInstaller::deactivate
     *
     * @param mixed ...$values
     * @return mixed
     */
    protected function resolveStatus(...$values)
    {
        if ($values) {
            $this->properties['status'] = $values[0];
            return $this->shop->save();
        }
        return $this->properties['status'] ?? static::STATUS_ACTIVE;
    }
}
