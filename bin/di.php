<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio;

use fn;
use OxidEsales\EshopCommunity\{Core\Exception\DatabaseException, Setup\Dispatcher};

return [
    'cli.commands' => static function (fn\Cli $cli, Core\Shop $shop) {
        try {
            $shop->id;
            yield 'setup:views' => new Cli\Setup\Views;
            $cli->command('setup:shop', require 'commands/setup-shop.php', ['action']);
            yield 'meta:model' => require 'commands/meta-model.php';
            yield 'meta:theme' => require 'commands/meta-theme.php';
        } catch (DatabaseException $e) {
        } finally {
            $cli->command('shop:modules', require 'commands/shop-modules.php', ['modules']);
            /**
             * Show shop configuration
             */
            $cli->command('shop:config', static function (Core\Shop $shop) {
                yield fn\traverse($shop->config, function ($value, $name) {
                    return [
                        'entry' => $name,
                        'value' => is_string($value) ? $value : json_encode($value, JSON_PRETTY_PRINT),
                        'type' => fn\type($value),
                    ];
                });
            });
        }
    },

    Dispatcher::class => static function() {
        require_once CORE_AUTOLOADER_PATH . '/../../Setup/functions.php';
        return new Dispatcher;
    }
];
