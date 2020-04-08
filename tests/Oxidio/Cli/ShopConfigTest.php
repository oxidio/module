<?php declare(strict_types=1);
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Cli;

use Oxidio\Functions;
use Php\Cli\IO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ShopConfigTest extends TestCase
{
    public function testIntegration(): void
    {
        (new ShopConfig())(new IO(new ArgvInput(), $out = new BufferedOutput()), Functions::shop(), true);
        self::assertNotEmpty($out->fetch());
    }
}
