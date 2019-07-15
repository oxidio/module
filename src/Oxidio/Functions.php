<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio;

use fn;
use OxidEsales\Eshop;
use Symfony\Component\Console\Input\{
    ArgvInput,
    InputInterface,
    InputOption
};

class Functions
{
    protected static $shops = [];

    protected const URL_PREFIX = 'OXIDIO_SHOP_';

    /**
     * @return string[]
     */
    public static function shopUrls(): array
    {
        return fn\traverse($_ENV ?? [], static function ($url, &$var) {
            if (strpos($var, static::URL_PREFIX) !== 0) {
                return null;
            }
            $var = str_replace('_', '-', strtolower(substr($var, strlen(static::URL_PREFIX))));
            return $url;
        });
    }

    /**
     * @param string|Core\Database $shop
     * @param array $params
     *
     * @return Core\Shop
     */
    public static function shop($shop = null, array $params = []): Core\Shop
    {
        is_string($shop) && $shop = self::shopUrls()[$shop] ?? $shop;
        $db = $shop instanceof Core\Database ? $shop : Core\Database::get($shop);
        $hash = md5(json_encode([spl_object_hash($db), $params]));
        return self::$shops[$hash] ?? self::$shops[$hash] = new Core\Shop($db, $params);
    }

    /**
     * @param fn\Package|string|array $package
     * @param string|callable|array ...$args
     *
     * @return fn\Cli
     */
    public static function cli($package, ...$args): fn\Cli
    {
        $cli = fn\cli(
            $package,
            (new DI\RegistryResolver)->container,
            [
                InputInterface::class => static function (): ArgvInput {
                    return new ArgvInput;
                },

                Core\Shop::class => static function (InputInterface $input): Core\Shop {
                    return static::shop($input->hasOption('shop') ? $input->getOption('shop') : null);
                },

                Core\Database::class => static function (Core\Shop $shop): Core\Database {
                    return $shop->db;
                },
            ],
            ...$args
        );

        if ($urls = implode(' | ', fn\keys(static::shopUrls()))) {
            $urls = "[ $urls ]";
        }

        $cli->getDefinition()->addOption(new InputOption(
            '--shop',
            's',
            InputOption::VALUE_REQUIRED,
            "Shop url 'mysql://<user>:<pass>@<host>:3306/<db>'" .
            "\nor entries from the .env file 'OXIDIO_SHOP_*' {$urls}"
        ));

        return $cli;
    }
}
