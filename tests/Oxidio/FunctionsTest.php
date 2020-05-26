<?php declare(strict_types=1);
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio;

use Php\test\assert;
use OxidEsales\EshopCommunity\Core\Database\Adapter\DatabaseInterface;
use Oxidio\Core\Database;
use Oxidio\Core\Shop;
use PHPUnit\Framework\TestCase;
use Php;
use Symfony\Component\Console\Output\BufferedOutput;

class FunctionsTest extends TestCase
{
    public function testCli(): void
    {
        self::assertCli('foobar', static function () {
            yield 'c1' => static function (Shop $shop, Database $db, string $opt) {
                self::assertSame($db, $shop->db);
                yield $shop . $opt;
            };
        });

        self::assertCli('bar', static function (Shop $shop) {
            yield 'c1' => static function (Database $db, string $opt) use ($shop) {
                self::assertSame($db, $shop->db);
                yield $shop . $opt;
            };
        });
    }

    private static function assertCli(string $expected, callable $callable): void
    {
        $_ENV = ['OXIDIO_SHOP_FOO' => DatabaseInterface::FETCH_MODE_BOTH];
        $cli = Functions::cli(Php\VENDOR\OXIDIO\OXIDIO, $callable);
        self::assertTrue($cli->getDefinition()->hasOption('shop'));
        $cli->setAutoExit(false);
        $_SERVER['argv'] = ['_', '--shop=foo', 'c1', '--opt=bar'];
        self::assertSame(0, $cli->run(null, $out = new BufferedOutput));
        self::assertSame($expected . PHP_EOL, $out->fetch());
        $_SERVER['argv'] = [];
        self::assertSame(0, $cli->run(null, $out));
        $content = $out->fetch();
        self::assertTrue(Php::every(
            [Php\VENDOR\OXIDIO\OXIDIO, 'Usage:', 'Options:', '[ foo ]', 'Available commands:'],
            static function ($token) use ($content) {
                return strpos($content, $token) !== false;
            })
        );
    }
}
