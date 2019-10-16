<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Bar\Cli;

use php;
use Oxidio;
use OxidEsales\Eshop\Core\Database\TABLE\OXCATEGORIES;

class Shop
{
    /**
     * test Oxidio\Core\Shop component
     *
     * @param php\Cli\IO $io
     * @param Oxidio\Core\Shop $shop
     */
    public function __invoke(php\Cli\IO $io, Oxidio\Core\Shop $shop)
    {
        foreach (php\flatten($shop->categories()) as $key => $cat) {
            $io->writeln(str_replace(['-', '/'], '_', strtoupper($key)) . ' : ' . $cat[OXCATEGORIES\OXTITLE]);
        }
    }
}
