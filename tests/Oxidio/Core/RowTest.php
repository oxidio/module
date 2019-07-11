<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core;

use fn;
use fn\test\assert;
use Oxidio;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Row
 */
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
            '2 args: a, b' => [fn\mapKey('v-a')->andValue('v-b'), 'a', 'b'],
            '2 args: a, c' => [fn\mapKey('v-a'), 'a', 'c'],
            '2 args: c, a' => [fn\mapValue('v-a'), 'c', 'a'],
            '2 args: c, d' => [fn\mapValue(), 'c', 'd'],
            '2 args: null, null' => [fn\mapValue(), null, null],
        ];
    }

    /**
     * @covers ::__invoke
     * @dataProvider providerInvoke
     *
     * @param $expected
     * @param array $args
     */
    public function testInvoke($expected, ...$args): void
    {
        $row = new Row(['A' => 'v-a', 'b' => 'v-b', 'c' => null]);

        assert\equals($expected, $row(...$args));
    }

    /**
     * @covers ::jsonSerialize
     */
    public function testJsonSerialize(): void
    {
        $row = new Row(['A' => 'v-a', 'b' => 'v-b', 'c' => null]);
        assert\same(json_encode($row()), json_encode($row));
    }

    /**
     * @covers ::__toString
     */
    public function testToString(): void
    {
        assert\same('', (string)new Row([]));
        assert\same('lowercase', (string)new Row(['OxId' => 'lowercase']));
    }
}
