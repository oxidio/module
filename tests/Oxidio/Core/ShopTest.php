<?php declare(strict_types=1);
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core;

use Oxidio;
use PHPUnit\Framework\TestCase;

class ShopTest extends TestCase
{
    public function testId(): void
    {
        self::assertSame(32, strlen($id = Shop::id('12345678901', '12', '12345678901234567890')));
        self::assertSame('12345678901121234567890123', substr($id, 0, -6));

        self::assertSame(32, strlen($id = Shop::id('12345678901234567890', '12345678901', '12')));
        self::assertSame('12345678901234561234567812', substr($id, 0, -6));

        self::assertSame(32, strlen($id = Shop::id('12345678901', '123456789', '1234567890', '12345')));
        self::assertSame('12345671234567123456712345', substr($id, 0, -6));
        self::assertSame(
            '1234567812345678123456781234567',
            Shop::id('12345678', '12345678', '12345678', '1234', '567')
        );
        self::assertSame(32, strlen($id = Shop::id('12345678', '12345678', '12345678', '1234', '5678')));
        self::assertSame('12345612345612345612345678', substr($id, 0, -6));
        self::assertSame(32, strlen(Shop::id()));
        self::assertSame(6, strlen(Shop::id(true)));
        self::assertSame(16, strlen(Shop::id(16)));
        self::assertSame(64, strlen(Shop::id(Shop::id(), Shop::id(), false)));
        self::assertSame('foo', Shop::id('foo'));
        self::assertSame('foobar', Shop::id('foo', 'bar'));
        self::assertSame(9, strlen($id = Shop::id('foo', true)));
        self::assertSame('foo', substr($id, 0, 3));
    }

    public function testUrls(): void
    {
        $_ENV = [];
        self::assertSame([], Shop::urls());
        $_ENV = [
            'OXIDIO_SHOP_FOO' => 'url-foo',
            'OXIDIO_SHOP_bar' => 'url-bar',
            'OXIDIO_SHOP_foo_BAR' => 'url-foo-bar',
            'does_not_match' => 'does_not_match',
        ];
        self::assertSame([
            'foo' => 'url-foo',
            'bar' => 'url-bar',
            'foo-bar' => 'url-foo-bar',
        ], Shop::urls());
    }

}
