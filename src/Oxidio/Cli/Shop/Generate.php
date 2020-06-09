<?php declare(strict_types=1);
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Cli\Shop;


use Generator;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ProjectConfigurationGenerator;

class Generate
{
    /**
     * Generates default project configuration
     *
     * @param ProjectConfigurationGenerator $generator
     * @return Generator
     */
    public function __invoke(ProjectConfigurationGenerator $generator): Generator
    {
        $generator->generate();
        yield '<info>ok</info>';
    }
}
