<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio;

use fn;
use OxidEsales\Eshop;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\{ArgvInput, InputInterface, InputOption};

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
     * @param string|Core\Database $locator
     * @param array $params
     *
     * @return Core\Shop
     */
    public static function shop($locator = null, array $params = []): Core\Shop
    {
        if (is_string($locator) && $shop = self::shopUrls()[$locator] ?? null) {
            $params += ['locator' => $locator];
        } else {
            $shop = $locator;
        }
        $db = $shop instanceof Core\Database ? $shop : Core\Database::get($shop);
        $hash = md5(json_encode([spl_object_hash($db), $params]));
        return self::$shops[$hash] ?? self::$shops[$hash] = new Core\Shop($db, $params);
    }

    private static function shopOption(): InputOption
    {
        if ($urls = implode(' | ', fn\keys(static::shopUrls()))) {
            $urls = "[ $urls ]";
        }
        return new InputOption(
            '--shop',
            null,
            InputOption::VALUE_REQUIRED,
            "Shop url 'mysql://<user>:<pass>@<host>:3306/<db>'" .
            "\nor entries from the .env file 'OXIDIO_SHOP_*' {$urls}"
        );
    }

    /**
     * @param fn\Package|string|array $package
     * @param string|callable|array ...$args
     *
     * @return fn\Cli
     */
    public static function cli($package = null, ...$args): fn\Cli
    {
        $cli = fn\cli(
            $package ?? [],
            (new DI\RegistryResolver)->container,
            [
                InputInterface::class => static function (fn\Cli $cli) : ArgvInput {
                    $input = new ArgvInput;
                    ($def = $cli->getDefinition())->addOption(self::shopOption());
                    try {
                        $input->bind($def);
                    } catch (ExceptionInterface $ignore) {
                    }
                    return $input;
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
        $cli->getDefinition()->hasOption('shop') || $cli->getDefinition()->addOption(self::shopOption());
        return $cli;
    }
}
