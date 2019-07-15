<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Bar\Cli;

use fn;
use Oxidio;
use OxidEsales\Eshop\Application\Model\Category;

class Shop
{
    /**
     * test Oxidio\Core\Shop component
     *
     * @param fn\Cli\IO $io
     * @param Oxidio\Core\Shop $shop
     */
    public function __invoke(fn\Cli\IO $io, Oxidio\Core\Shop $shop)
    {
        foreach (fn\flatten($shop->categories()) as $key => $cat) {
            $io->writeln(str_replace(['-', '/'], '_', strtoupper($key)) . ' : ' . $cat[Category\TITLE]);
        }
    }
}
