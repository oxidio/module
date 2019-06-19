<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core;

use fn\test\assert;
use fn;
use Oxidio;

/**
 * @coversDefaultClass Modify
 */
class ModifyTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @covers ::insert
     * @covers ::update
     * @covers ::delete
     */
    public function testModify(): void
    {
        $modify = (new Modify('view'))->withDb(static function() {
            return 1;
        });

        $values = static function () {
            return fn\map(['a' => 'A', 'b' => true, 'c' => false, 'd' => null]);
        };

        assert\equals(
            ["INSERT INTO view (\n  a, b, c, d\n) VALUES (\n  :a, :b, :c, :d\n)" => 1],
            $modify->insert($values)()
        );

        assert\equals(
            ["UPDATE view SET\n  a = :a,\n  b = :b,\n  c = :c,\n  d = :d\nWHERE (foo = 'bar' AND bar = 'foo')" => 1],
            $modify->update($values, ['foo' => 'bar', 'bar' => 'foo'])()
        );

        assert\equals(
            ["DELETE FROM view\nWHERE (foo = 'bar' AND bar = 'foo')" => 1],
            $modify->delete(['foo' => 'bar', 'bar' => 'foo'])()
        );

        assert\equals(
            [
                "INSERT INTO view (\n  a, b\n) VALUES (\n  :a, :b\n)" => 2,
                "INSERT INTO view (\n  a, b, c\n) VALUES (\n  :a, :b, :c\n)" => 1,
            ],
            $modify->insert(['a' => 1, 'b' => 1], ['a' => 2, 'b' => 2], ['a' => 3, 'b' => 3, 'c' => 3])()
        );
    }
}
