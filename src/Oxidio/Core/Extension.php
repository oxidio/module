<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core;

use Php;
use JsonSerializable;
use Oxidio\Enum\Tables as T;

/**
 * @property string        $status
 * @property-read string   $id
 * @property-read string   $type
 * @property-read string   $version
 * @property-read string   $path
 * @property-read string[] $controllers
 * @property-read array[]  $events
 * @property-read string[] $classes aModuleFiles|aModules|aModuleExtensions ['cl', 'ox-cl' => 'cl']
 * @property-read array    $templates
 * @property-read array    $files
 * @property Php\Map       $config
 *
 */
class Extension implements JsonSerializable
{
    use Php\PropertiesTrait;
    use Php\PropertiesTrait\Init;

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
     * @param Shop $provider
     * @param iterable $data
     */
    public function __construct(Shop $provider, $data)
    {
        $this->shop = $provider;
        $this->propsInit($data);
    }

    /**
     * @param Shop $shop
     *
     * @return array
     */
    protected static function shopData(Shop $shop): array
    {
        $from = Php::str('(SELECT ' .
            Php\map([
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
                'c' => T::CONFIG . ' c',
                'cd' => T::CONFIGDISPLAY . ' cd',
                'c.mod' => 'c.' . T\CONFIG::MODULE,
                'c.shop' => 'c.' . T\CONFIG::SHOPID,
                'c.var' => 'c.' . T\CONFIG::VARNAME,
                'c.val' => 'c.' . T\CONFIG::VARVALUE,
                'c.type' => 'c.' . T\CONFIG::VARTYPE,
                'cd.mod' => 'cd.' . T\CONFIGDISPLAY::CFGMODULE,
                'cd.var' => 'cd.' . T\CONFIGDISPLAY::CFGVARNAME,
                'cd.gr' => 'cd.' . T\CONFIGDISPLAY::GROUPING,
                'cd.pos' => 'cd.' . T\CONFIGDISPLAY::POS,
            ]
        );

        $query = $shop->query($from, ['shop' => $shop->id])->orderBy('module', 'gr', 'pos', 'name');
        return Php\traverse(Php\map($query,
            static function (array $row) {
                ['module' => $module, 'value' => $value, 'name' => $name] = $row;
                strpos($row['type'], 'rr') && $value = unserialize($value, [null]);
                if ($module && strpos($module, ':') === false) {
                    $module = static::MODULE . ':' . $module;
                }
                return Php\mapGroup($module)->andKey($name)->andValue($value);
            }
        ), static function (array $config, $module) {
            [$type, $module] = explode(':', $module . ':');
            return Php\mapKey((string)$module)->andValue([
                'config' => $config,
                'type' => (string)$type,
            ]);
        });
    }

    /**
     * @param Shop $shop
     * @return Php\Map|static[]
     */
    public static function all(Shop $shop): Php\Map
    {
        $data = static::shopData($shop);
        $conf = $data[self::SHOP]['config'] ?? [];

        $attr = function (array $data, $attr): array {
            return Php\traverse($data, static function ($value, $module) use ($attr) {
                return Php\mapKey($module)->andValue([$attr => $value]);
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
        foreach (Php\keys(self::CONFIG_KEYS) as $key) {
            unset($data[self::SHOP]['config'][$key]);
        }

        return Php\map($data, static function (array $ext, $id) use ($shop) {
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

    /**
     * @see $config
     * @return Php\Map
     */
    protected function resolveConfig(): Php\Map
    {
        return Php\map($this->properties['config'] ?? []);
    }
}
