<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio;

use fn\test\assert;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Functions
 */
class FunctionsTest extends TestCase
{
    /**
     * @covers \Oxidio\Functions::shopUrls
     */
    public function testShopUrls(): void
    {
        $_ENV = [];
        assert\same([], Functions::shopUrls());
        $_ENV = [
            'OXIDIO_SHOP_FOO' => 'url-foo',
            'OXIDIO_SHOP_bar' => 'url-bar',
            'OXIDIO_SHOP_foo_BAR' => 'url-foo-bar',
            'does_not_match' => 'does_not_match',
        ];
        assert\same([
            'foo' => 'url-foo',
            'bar' => 'url-bar',
            'foo-bar' => 'url-foo-bar',
        ], Functions::shopUrls());
    }
}
