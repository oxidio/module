<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core;

use php\test\assert;
use php;
use Oxidio;
use OxidEsales\Eshop\{
    Core\Database\TABLE,
    Core\Database\TABLE\OXCOUNTRY
};
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass DataModify
 */
class DataModifyTest extends TestCase
{
    /**
     */
    public function testModify(): void
    {
        $modify = (new DataModify('view'))->withDb(static function () {
            return 1;
        });

        $values = static function () {
            return php\map(['a' => 'A', 'b' => true, 'c' => false, 'd' => null]);
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

        assert\equals([
            "INSERT INTO view (\n  a, b\n) VALUES (\n  :a, ENCODE(:b, 'pass')\n)" => 1
        ], $modify->insert([
            'a' => 'A',
            'b' => function($column) {
                return ["ENCODE(:$column, 'pass')" => null];
            },
        ])());
    }

    public function testIntegration(): void
    {
        assert\type(Shop::class, $shop = Oxidio\shop());
        assert\type(DataModify::class, $modify = $shop->modify(TABLE\OXCOUNTRY));

        assert\type('callable', $modify->delete([OXCOUNTRY\OXID => ['LIKE', 'test-%']]));
        self::assertCommit([['DELETE|LIKE' => 0]], $shop->commit());
        assert\same(0, $shop->query(TABLE\OXCOUNTRY, [OXCOUNTRY\OXID => ['LIKE', 'test-%']])->total);

        assert\type(
            'callable',
            $modify->insert(
                [OXCOUNTRY\OXID => 'test-a', OXCOUNTRY\OXTITLE => 'test-a'],
                [OXCOUNTRY\OXID => 'test-b', OXCOUNTRY\OXTITLE => 'test-b'],
                [OXCOUNTRY\OXID => 'test-c', OXCOUNTRY\OXTITLE => 'test-c', OXCOUNTRY\OXACTIVE => true]
            )
        );
        self::assertCommit([['INSERT INTO' => 2, 'INSERT INTO|active' => 1]], $shop->commit());
        self::assertCommit([], $shop->commit());
        assert\type(
            'callable',
            $modify->update([OXCOUNTRY\OXSHORTDESC => 'test'], [OXCOUNTRY\OXID => ['LIKE', 'test-%']])
        );
        self::assertCommit([['UPDATE' => 3]], $shop->commit());

        assert\type(
            'callable',
            $modify->map(['test-d' => 'test-D', 'test-c' => 'test-C'], static function (DataModify $modify, $value, $key) {
                yield $modify->insert([OXCOUNTRY\OXID => "$key-first", OXCOUNTRY\OXTITLE => "$value-first"]);
                yield $modify->insert([OXCOUNTRY\OXTITLE => "$value-second", OXCOUNTRY\OXID => "$key-second"]);
                yield $modify->update(
                    [OXCOUNTRY\OXTITLE => function ($column) { return "UPPER($column)";}],
                    [OXCOUNTRY\OXID => ['LIKE', "$key-%"]]
                );
            })
        );
        self::assertCommit([
            ['INSERT|id,' => 1],
            ['INSERT|title,' => 1],
            ['UPDATE|= UPPER(' => 2],
            ['INSERT|id,' => 1],
            ['INSERT|title,' => 1],
            ['UPDATE|= UPPER(' => 2],
        ], $shop->commit());

        assert\same(1, $shop->query(TABLE\OXCOUNTRY, [OXCOUNTRY\OXTITLE => 'TEST-C-FIRST'])->total);

        $modify->replace(static function () {
            yield 'test-a' => [OXCOUNTRY\OXTITLE => 'test-a-replaced'];
            yield 'test-e' => [OXCOUNTRY\OXTITLE => 'test-e-new', OXCOUNTRY\OXACTIVE => true];
            yield 'test-b' => null;
            yield 'test-b' => null;
            yield 'test-D-first' => null;
        }, OXCOUNTRY\OXID);

        self::assertCommit([[
            'INSERT' => 2,
            'INSERT|active' => 1,
            'DELETE' => 2,
        ]], $shop->commit());

        $modify->delete([OXCOUNTRY\OXID => ['LIKE', 'test-%']]);
        self::assertCommit([['DELETE|LIKE' => 6]], $shop->commit());
    }

    private static function assertCommit(array $expected, iterable $commit): void
    {
        $commit = php\traverse($commit, static function (array $counts) {
            return php\map($counts, static function (int $count, string $sql) {
                return ['sql' => $sql, 'count' => $count];
            })->values;
        });
        assert\same(count($expected), count($commit));
        foreach ($expected as $i => $counts) {
            $j = 0;
            foreach ($counts as $sql => $count) {
                assert\same($count, $commit[$i][$j]['count'] ?? null);
                foreach (explode('|', $sql) as $token) {
                    self::assertStringContainsString($token, $commit[$i][$j]['sql'] ?? null);
                }
                $j++;
            }
        }
    }
}
