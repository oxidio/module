<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio
{
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

}
