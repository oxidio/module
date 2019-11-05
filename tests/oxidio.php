#!/usr/bin/env php
<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio;

use OxidEsales\Eshop\Core\{Config, Module};
use Php;

call_user_func(static function () {
    $file = file_exists($file = __DIR__ . '/../../../autoload.php') ? $file :  __DIR__ . '/../vendor/autoload.php';
    /** @noinspection PhpIncludeInspection */
    exit(call_user_func(require $file, static function () {
        return cli('oxidio/oxidio', static function (Php\Cli $cli) {

            yield 'modules' => static function (Config $config) {
                $dir = $config->getModulesDir();
                foreach (oxNew(Module\ModuleList::class)->getModulesFromDir($dir) as $module) {
                    yield $module->getId();
                }
            };

            $cli->command('shop:property', static function ($property, Core\Shop $shop) {
                $value = $shop->$property ?? $shop->$property();
                yield is_scalar($value) ? $value : (object)$value;
            }, ['property']);

            $cli->command('test:dml', require 'commands/test-dml.php', ['action', 'name']);
            $cli->command('test:query', require 'commands/test-query.php', ['columns']);

            yield 'test:delivery' => require 'commands/test-delivery.php';

        })->run();
    }));
});
