<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio;

use Php;
use OxidEsales\Eshop;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\{ArgvInput, InputInterface, InputOption};

class Functions
{
    private static function shopOption(): InputOption
    {
        if ($urls = implode(' | ', Php::keys(Core\Shop::urls()))) {
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
     * @deprecated
     *
     * @param Php\Package|string|array $package
     * @param string|callable|array    ...$args
     *
     * @return Php\Cli
     */
    public static function cli($package = null, ...$args): Php\Cli
    {
        $cli = Php\Cli::fromPackage(
            $package ?? [],
            (new DI\RegistryResolver)->container,
            [
                InputInterface::class => static function (Php\Cli $cli) : ArgvInput {
                    $input = new ArgvInput;
                    ($def = $cli->getDefinition())->addOption(self::shopOption());
                    try {
                        $input->bind($def);
                    } catch (ExceptionInterface $ignore) {
                    }
                    return $input;
                },

                Core\Shop::class => static function (InputInterface $input): Core\Shop {
                    return Core\Shop::get($input->hasOption('shop') ? $input->getOption('shop') : null);
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
