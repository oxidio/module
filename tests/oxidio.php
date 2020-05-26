#!/usr/bin/env php
<?php declare(strict_types=1);
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio;

use OxidEsales\Eshop\Core\{Config, Module};
use Php;

call_user_func(static function () {
    $file = file_exists($file = __DIR__ . '/../../../autoload.php') ? $file : null;
    $file || $file = file_exists($file = __DIR__ . '/../vendor/autoload.php') ? $file : getcwd() . '/vendor/autoload.php';
    /** @noinspection PhpIncludeInspection */
    exit(call_user_func(require $file, static function () {
        $di = Php\Cli::di(Oxidio::di(), function (Php\Cli $cli) {
            // broken
            yield 'modules' => static function (Config $config) {
                $dir = $config->getModulesDir();
                foreach (oxNew(Module\ModuleList::class)->getModulesFromDir($dir) as $module) {
                    yield $module->getId();
                }
            };

            yield 'test:delivery' => require 'commands/test-delivery.php';
        });
        $di->set(Core\Shop::class, ($shop = new Cli\Shop\OptionProvider())->getFactory());
        $cli = new Php\Cli($di);

        $shop->addTo($cli->command('shop:property', static function ($property, Core\Shop $shop) {
            $value = $shop->$property ?? $shop->$property();
            yield is_scalar($value) ? $value : (object)$value;
        }, ['property']));

        $shop->addTo($cli->command('test:dml', require 'commands/test-dml.php', ['action', 'name']));
        $shop->addTo($cli->command('test:query', require 'commands/test-query.php', ['columns']));

        return $cli->run();
    }));
});
