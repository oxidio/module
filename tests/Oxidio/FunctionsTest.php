<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio;

use fn\test\assert;
use OxidEsales\EshopCommunity\Core\Database\Adapter\DatabaseInterface;
use Oxidio\Core\Database;
use Oxidio\Core\Shop;
use PHPUnit\Framework\TestCase;
use fn;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @coversDefaultClass Functions
 */
class FunctionsTest extends TestCase
{
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

    public function testCli(): void
    {
        self::assertCli('foo', static function () {
            yield 'c1' => static function (Shop $shop, Database $db) {
                assert\same($db, $shop->db);
                yield (string)$shop;
            };
        });

        self::assertCli('', static function (Shop $shop) {
            yield 'c1' => static function (Database $db) use ($shop) {
                assert\same($db, $shop->db);
                yield (string)$shop;
            };
        });
    }

    private static function assertCli(string $expected, callable $callable): void
    {
        $_ENV = ['OXIDIO_SHOP_FOO' => DatabaseInterface::FETCH_MODE_BOTH];
        $cli = cli(fn\VENDOR\OXIDIO\OXIDIO, $callable);
        assert\true($cli->getDefinition()->hasOption('shop'));
        $cli->setAutoExit(false);
        assert\type(fn\Cli::class, $cli);
        $_SERVER['argv'] = ['_', '--shop=foo', 'c1'];
        assert\same(0, $cli->run(null, $out = new BufferedOutput));
        assert\same($expected . PHP_EOL, $out->fetch());
        $_SERVER['argv'] = [];
        assert\same(0, $cli->run(null, $out));
        $content = $out->fetch();
        assert\true(fn\every(
            [fn\VENDOR\OXIDIO\OXIDIO, 'Usage:', 'Options:', '[ foo ]', 'Available commands:'],
            static function ($token) use ($content) {
                return strpos($content, $token) !== false;
            })
        );
    }
}
