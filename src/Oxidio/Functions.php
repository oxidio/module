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
     * @param string $package
     * @param string|callable|array ...$args
     *
     * @return fn\Cli
     */
    public static function cli(string $package = null, ...$args): fn\Cli
    {
        $lib = $package ? fn\PACKAGES[$package] ?? [] : [];
        $libDir = $lib['dir'] ?? null;

        $fns    = [];
        $config = [];
        foreach ($args as $arg) {
            if (fn\isCallable($arg)) {
                $fns[] = $arg;
            } else {
                $config[] = is_string($arg) && $libDir && $arg[0] !== DIRECTORY_SEPARATOR ? $libDir . $arg : $arg;
            }
        }

        return fn\di((new DI\RegistryResolver)->container, [
            'cli.name' => $lib['name'] ?? null,
            'cli.version' => $lib['version'] ?? null,
            'cli.commands.default' => false,

            InputInterface::class => static function (): ArgvInput {
                return new ArgvInput;
            },

            Core\Shop::class => static function (InputInterface $input): Core\Shop {
                return static::shop($input->hasOption('shop') ? $input->getOption('shop') : null);
            },

            fn\Cli::class => static function(fn\DI\Container $container) use ($fns) {
                $cli = fn\cli($container);
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

                $container->set(fn\Cli::class, $cli);
                foreach ($fns as $fn) {
                    $result = $container->call($fn);
                    foreach (is_iterable($result) ? $result : [] as $name => $command) {
                        $cli->command($name, ...array_values(is_array($command) ? $command : [$command]));
                    }
                }
                return $cli;
            },

        ], ...$config)->get(fn\Cli::class);
    }
}
