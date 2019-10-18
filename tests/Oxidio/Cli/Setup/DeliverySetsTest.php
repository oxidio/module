<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Cli\Setup;

use Oxidio\Functions;
use PHPUnit\Framework\TestCase;

class DeliverySetsTest extends TestCase
{
    public function testIntegration(): void
    {
        $closure = include __DIR__ . '/../../../commands/test-delivery.php';
        self::assertNotEmpty(iterator_to_array($closure(Functions::shop(), false)));
    }
}
