<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio;

use DI;
use fn;
use OxidEsales\EshopCommunity\{Core\Exception\DatabaseException, Setup\Dispatcher};

return [
    fn\Cli::class => DI\decorate(static function (fn\Cli $cli, DI\Container $container) {
        try {
            $container->get(Core\Shop::class)->id;
            $cli->command('setup:views', new Cli\Setup\Views);
            $cli->command('setup:shop', require 'commands/setup-shop.php', ['action']);
            $cli->command('meta:model', require 'commands/meta-model.php');
            $cli->command('meta:theme', require 'commands/meta-theme.php');
        } catch (DatabaseException $e) {
        } finally {
            $cli->command('shop:modules', require 'commands/shop-modules.php', ['modules']);
        }
        return $cli;
    }),

    Dispatcher::class => static function() {
        require_once CORE_AUTOLOADER_PATH . '/../../Setup/functions.php';
        return new Dispatcher;
    }
];
