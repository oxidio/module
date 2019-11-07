<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio
{
    use OxidEsales\Eshop;

    /**
     * @param mixed ...$args
     *
     * @return Core\DataQuery
     */
    function query(...$args): Core\DataQuery
    {
        return Core\Database::get()->query(...$args);
    }

}
