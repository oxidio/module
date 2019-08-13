<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio;

use php;
use OxidEsales\EshopCommunity\{Core\Exception\DatabaseException, Setup\Dispatcher};
use Oxidio\Cli\ShopConfig;

return [
    'cli.commands' => static function (php\Cli $cli, Core\Shop $shop) {
        try {
            $shop->id;
            yield 'setup:views' => new Cli\Setup\Views;
            $cli->command('setup:shop', require 'commands/setup-shop.php', ['action']);
            yield 'meta:model' => require 'commands/meta-model.php';
            yield 'meta:theme' => require 'commands/meta-theme.php';
        } catch (DatabaseException $e) {
        } finally {
            $cli->command('shop:modules', require 'commands/shop-modules.php', ['modules']);
            $cli->command('shop:config', new ShopConfig);
        }
    },

    Dispatcher::class => static function() {
        require_once CORE_AUTOLOADER_PATH . '/../../Setup/functions.php';
        return new Dispatcher;
    }
];
