<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Module;

use fn;
use OxidEsales\Eshop\Core\Registry;
use Psr\Container\ContainerInterface;

/**
 */
class Provider
{
    /**
     * @var Module[]
     */
    protected static $cache = [];

    /**
     * Get a module (create it if necessary)
     *
     * @param string $id DI config file | directory | identifier
     *
     * @return Module
     */
    public static function module(string $id): Module
    {
        return self::$cache[$id] ?? self::create($id);
    }

    /**
     * @param string $id
     * @return bool
     */
    public static function has(string $id): bool
    {
        return isset(self::$cache[$id]);
    }

    /**
     * @param string $id
     * @return Module
     */
    private static function create(string $id): Module
    {
        $ids = Registry::getUtils()->fromPhpFileCache(__METHOD__) ?: [];
        $id = $ids[$id] ?? $id;

        $module = new Module($di = fn\di(Module::CONFIG, self::id($id), self::container()));
        $di->set(Module::class, $module);

        if (!isset($ids[$module->id])) {
            $ids[$module->id] = $id;
            Registry::getUtils()->toPhpFileCache(__METHOD__, $ids);
        }
        return self::$cache[$id] = self::$cache[$module->id] = $module;
    }

    /**
     * @see \fn\Composer\DIClassLoader::__invoke
     *
     * @return ContainerInterface
     */
    private static function container(): ContainerInterface
    {
        return call_user_func(require fn\VENDOR_DIR . 'autoload.php', function (ContainerInterface $container) {
            return $container;
        });
    }

    /**
     * @param string $id
     * @return array|string
     */
    private static function id(string $id)
    {
        if (is_file($id)) {
            return $id;
        }
        if (is_dir($dir = $id)) {
            $path = [];
            while('modules' !== ($name = basename($dir)) && ($dir = dirname($dir)) !== '/') {
                array_unshift($path, $name) ;
            }
            $id = implode('/', $path);
        }
        return [ID => $id];
    }
}
