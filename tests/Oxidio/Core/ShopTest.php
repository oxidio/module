<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core;

use fn\test\assert;
use Oxidio;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Shop
 */
class ShopTest extends TestCase
{
    public function testId(): void
    {
        assert\same(32, strlen($id = Shop::id('12345678901', '12', '12345678901234567890')));
        assert\same('12345678901121234567890123', substr($id, 0, -6));

        assert\same(32, strlen($id = Shop::id('12345678901234567890', '12345678901', '12')));
        assert\same('12345678901234561234567812', substr($id, 0, -6));

        assert\same(32, strlen($id = Shop::id('12345678901', '123456789', '1234567890', '12345')));
        assert\same('12345671234567123456712345', substr($id, 0, -6));
        assert\same(
            '1234567812345678123456781234567',
            Shop::id('12345678', '12345678', '12345678', '1234', '567')
        );
        assert\same(32, strlen($id = Shop::id('12345678', '12345678', '12345678', '1234', '5678')));
        assert\same('12345612345612345612345678', substr($id, 0, -6));
        assert\same(32, strlen(Shop::id()));
        assert\same(6, strlen(Shop::id(true)));
        assert\same(16, strlen(Shop::id(16)));
        assert\same(64, strlen(Shop::id(Shop::id(), Shop::id(), false)));
        assert\same('foo', Shop::id('foo'));
        assert\same('foobar', Shop::id('foo', 'bar'));
        assert\same(9, strlen($id = Shop::id('foo', true)));
        assert\same('foo', substr($id, 0, 3));
    }
}
