<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio
{
    use Php;
    use OxidEsales\Eshop;

    function db(...$args): Core\Database
    {
        return Core\Database::get(...$args);
    }

    /**
     * @param mixed ...$args
     *
     * @return Core\DataQuery
     */
    function query(...$args): Core\DataQuery
    {
        return db()->query(...$args);
    }

    /**
     * @param string|Core\Database $shop
     * @param array $params
     *
     * @return Core\Shop
     */
    function shop($shop = null, array $params = []): Core\Shop
    {
        return Functions::shop(...func_get_args());
    }

    /**
     * @param Php\Package|string|array $package
     * @param string|callable|array    ...$args
     *
     * @return Php\Cli
     */
    function cli($package = null, ...$args): Php\Cli
    {
        return Functions::cli(...func_get_args());
    }
}
