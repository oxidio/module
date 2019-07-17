<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio;

use fn\test\assert;
use OxidEsales\EshopCommunity\Core\Database\Adapter\DatabaseInterface;
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
        $_ENV = ['OXIDIO_SHOP_FOO' => DatabaseInterface::FETCH_MODE_BOTH];
        $cli = cli(fn\VENDOR\OXIDIO\OXIDIO, static function () {
            yield 'c1' => static function (Shop $shop) {
                yield (string)$shop;
            };
        });
        assert\true($cli->getDefinition()->hasOption('shop'));
        $cli->setAutoExit(false);
        assert\type(fn\Cli::class, $cli);
        $_SERVER['argv'] = ['_', '--shop=foo', 'c1'];
        assert\same(0, $cli->run(null, $out = new BufferedOutput));
        assert\same('foo' . PHP_EOL, $out->fetch());
    }
}
