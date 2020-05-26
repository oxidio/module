<?php declare(strict_types=1);
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Cli\Setup;

use Oxidio\Core;
use PHPUnit\Framework\TestCase;

class DeliverySetsTest extends TestCase
{
    public function testIntegration(): void
    {
        $closure = include __DIR__ . '/../../../commands/test-delivery.php';
        self::assertNotEmpty(iterator_to_array($closure(Core\Shop::get(), false)));
    }
}
