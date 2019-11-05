<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Bar\Cli;

use Php;
use Oxidio;

class Shop
{
    /**
     * test Oxidio\Core\Shop component
     *
     * @param Php\Cli\IO       $io
     * @param Oxidio\Core\Shop $shop
     */
    public function __invoke(Php\Cli\IO $io, Oxidio\Core\Shop $shop)
    {
        $io->writeln($shop->id);
    }
}
