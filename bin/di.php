<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

use OxidEsales\EshopCommunity\{Core\Exception\DatabaseException, Setup\Dispatcher};
use Psr\Container\ContainerInterface;

return [
    fn\Cli::class => static function(ContainerInterface $container) {
        $cli = fn\cli($container);
        try {
            Oxidio\shop()->id;
            $cli->command('setup:views', require 'commands/setup-views.php');
            $cli->command('setup:shop', require 'commands/setup-shop.php', ['action']);
            $cli->command('meta:model', require 'commands/meta-model.php');
            $cli->command('meta:theme', require 'commands/meta-theme.php');
            $cli->command('meta:test', require 'commands/meta-test.php');
        } catch (DatabaseException $e) {
        } finally {
            $cli->command('shop:modules', require 'commands/shop-modules.php', ['modules']);
        }
        return $cli;
    },

    Dispatcher::class => static function() {
        require_once CORE_AUTOLOADER_PATH . '/../../Setup/functions.php';
        return new Dispatcher;
    }
];
