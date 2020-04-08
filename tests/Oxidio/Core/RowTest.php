<?php declare(strict_types=1);
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core;

use Php;
use Oxidio;
use PHPUnit\Framework\TestCase;

class RowTest extends TestCase
{
    public function providerInvoke(): array
    {
        return [
            '0 args' => [['a' => 'v-a', 'b' => 'v-b', 'c' => null]],
            '1 arg: a' => ['v-a', 'a'],
            '1 arg: b' => ['v-b', 'b'],
            '1 arg: c' => [null, 'c'],
            '1 arg: d' => [null, 'd'],
            '1 arg: null' => [null, null],
            "1 arg: ['a', 'b', 'D']" => [['a' => 'v-a', 'b' => 'v-b'], ['a', 'b', 'D']],
            "1 arg: ['a', 'b', 'c' => 'D']" => [['a' => 'v-a', 'b' => 'v-b', 'D' => null], ['a', 'b', 'c' => 'D']],
            '2 args: a, b' => [Php::mapKey('v-a')->andValue('v-b'), 'a', 'b'],
            '2 args: a, c' => [Php::mapKey('v-a'), 'a', 'c'],
            '2 args: c, a' => [Php::mapValue('v-a'), 'c', 'a'],
            '2 args: c, d' => [Php::mapValue(), 'c', 'd'],
            '2 args: null, null' => [Php::mapValue(), null, null],
        ];
    }

    /**
     * @dataProvider providerInvoke
     *
     * @param $expected
     * @param array $args
     */
    public function testInvoke($expected, ...$args): void
    {
        $row = new Row(['A' => 'v-a', 'b' => 'v-b', 'c' => null]);

        self::assertEquals($expected, $row(...$args));
    }

    public function testJsonSerialize(): void
    {
        $row = new Row(['A' => 'v-a', 'b' => 'v-b', 'c' => null]);
        self::assertSame(json_encode($row()), json_encode($row));
    }

    public function testToString(): void
    {
        self::assertSame('', (string)new Row([]));
        self::assertSame('lowercase', (string)new Row(['OxId' => 'lowercase']));
    }
}
