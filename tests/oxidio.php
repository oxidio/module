#!/usr/bin/env php
<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio;

use OxidEsales\Eshop\Core\{
    Module,
    Registry
};
use fn;

call_user_func(static function () {
    $file = file_exists($file = __DIR__ . '/../../../autoload.php') ? $file : __DIR__ . '/../vendor/autoload.php';
    /** @noinspection PhpIncludeInspection */
    exit(call_user_func(require $file, static function () {
        $cli = fn\cli([
            'cli.name' => 'oxidio/oxidio',
            'cli.commands.default' => false,
        ]);

        $cli->command('modules', static function () {
            $dir = Registry::getConfig()->getModulesDir();
            foreach (oxNew(Module\ModuleList::class)->getModulesFromDir($dir) as $module) {
                yield $module->getId();
            }
        });

        $cli->command('shop:property', static function ($property, $shop = null) {
            $shop = shop($shop);
            $value = $shop->$property ?? $shop->$property();
            yield is_scalar($value) ? $value : (object)$value;
        }, ['property']);

        $cli->command('test:dml', require 'commands/test-dml.php', ['action', 'name']);
        $cli->command('test:query', require 'commands/test-query.php', ['columns']);
        $cli->command('test:delivery', require 'commands/test-delivery.php');

        return $cli->run();
    }));
});
