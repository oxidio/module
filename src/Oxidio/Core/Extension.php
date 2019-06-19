<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core;

use fn;
use JsonSerializable;
use OxidEsales\Eshop\Core\Database\TABLE;
use function Oxidio\shop;

/**
 * @property-read string $id
 * @property-read string $type
 * @property-read string $path aModulePaths
 * @property-read string $version aModuleVersions
 * @property-read string[] $controllers aModuleControllers
 * @property-read array[] $events aModuleEvents
 * @property-read string[] $classes aModuleFiles|aModules|aModuleExtensions ['cl', 'ox-cl' => 'cl']
 * @property-read bool|null $active aDisabledModules
 * @property-read array $templates aModuleTemplates
 * @property-read array $files aModuleFiles
 * @property-read array $config
 *
 */
class Extension implements JsonSerializable
{
    use fn\PropertiesReadOnlyTrait;

    public const SHOP = '';
    public const MODULE = 'module';
    public const THEME = 'theme';

    /**
     * @param Shop $shop
     *
     * @return array
     */
    protected static function shopData(Shop $shop): array
    {
        $from = fn\str('(SELECT ' .
            fn\map([
                '{c.mod} module',
                '{c.var} name',
                '{c.type} type',
                "DECODE({c.val}, '{pass}') value",
                '{cd.gr} gr',
                '{cd.pos} pos',
            ])->string(', ') .
            ' FROM {c} LEFT JOIN {cd} ON {c.mod} = {cd.mod} AND {c.var} = {cd.var}) config',
            [
                'pass' => $shop::CONFIG_KEY,
                'c' => TABLE\OXCONFIG . ' c',
                'cd' => TABLE\OXCONFIGDISPLAY . ' cd',
                'c.mod' => 'c.' . TABLE\OXCONFIG\OXMODULE,
                'c.var' => 'c.' . TABLE\OXCONFIG\OXVARNAME,
                'c.val' => 'c.' . TABLE\OXCONFIG\OXVARVALUE,
                'c.type' => 'c.' . TABLE\OXCONFIG\OXVARTYPE,
                'cd.mod' => 'cd.' . TABLE\OXCONFIGDISPLAY\OXCFGMODULE,
                'cd.var' => 'cd.' . TABLE\OXCONFIGDISPLAY\OXCFGVARNAME,
                'cd.gr' => 'cd.' . TABLE\OXCONFIGDISPLAY\OXGROUPING,
                'cd.pos' => 'cd.' . TABLE\OXCONFIGDISPLAY\OXPOS,
            ]
        );

        return fn\traverse(fn\map($shop->query($from)->orderBy('module', 'gr', 'pos', 'name'),
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
     * @param Shop|null $shop
     * @return fn\Map|static[]
     */
    public static function all(Shop $shop = null): fn\Map
    {
        $data = static::shopData($shop ?: shop());
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
            $versions = $attr($conf['aModuleVersions'] ?? [], 'version'),
            array_fill_keys($conf['aDisabledModules'] ?? [], ['active' => false])
        );

        foreach (['aModuleEvents', 'aModuleFiles', 'aModulePaths', 'aModuleTemplates', 'aModuleControllers', 'aModuleVersions', 'aDisabledModules'] as $key) {
            unset($data[self::SHOP]['config'][$key]);
        }
        return fn\map($data, static function (array $ext, $id) {
            $obj = new static;
            $obj->properties = $ext + [
                'id' => $id,
                'type' => static::MODULE,
                'version' => null,
                'active' => true,
                'path' => null,
                'config' => [],
                'templates' => [],
                'controllers' => [],
                'events' => [],
                'files' => [],
            ];
            return $obj;
        });
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'version' => $this->version,
            'active' => $this->active,
            'path' => $this->path,
            'config' => $this->config,
            'templates' => $this->templates,
            'controllers' => $this->controllers,
            'events' => $this->events,
            'files' => $this->files,
        ];
    }
}
