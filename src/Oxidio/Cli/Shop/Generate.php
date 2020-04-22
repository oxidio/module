<?php declare(strict_types=1);
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Cli\Shop;


use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ProjectConfigurationGenerator;

class Generate
{
    public function __invoke(
        ProjectConfigurationGenerator $generator
    ) {
        $generator->generate();
        yield '<info>ok</info>';
    }
}
