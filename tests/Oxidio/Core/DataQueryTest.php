<?php declare(strict_types=1);
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core;

use OxidEsales\Eshop\Application\Model;
use Oxidio;
use Oxidio\Enum\Tables as T;
use PHPUnit\Framework\TestCase;

class DataQueryTest extends TestCase
{
    public function testToString(): void
    {
        self::assertSame(
            "SELECT *\nFROM view",
            (string)Database::get()->query(...['view'])
        );

        self::assertSame(
            "SELECT *\nFROM view\nWHERE (c1 = 'v1')",
            (string)Database::get()->query(...['view', ['c1' => 'v1']])
        );

        self::assertSame(
            "SELECT *\nFROM view\nWHERE (c1 > '0' AND c2 < '1') OR (c3 IS NULL AND c4 LIKE '%') OR (c5 IN ('in1', 'in2'))",
            (string)Database::get()->query(...['view', ['c1' => ['>', 0], 'c2' => ['<', 'value' => 1]], [['column' => 'c3', 'value' => null], 'c4' => ['value' => '%', 'op' => 'LIKE']], ['c5' => ['IN', ['in1', 'in2']]]])
        );

        self::assertSame(
            "SELECT *\nFROM view",
            (string) Database::get()->query(...['view'])->where()->limit(0)->orderBy()
        );

        self::assertSame(
            "SELECT *\nFROM view\nLIMIT 0, 1",
            (string) Database::get()->query(...['view'])->limit(1)
        );

        self::assertSame(
            "SELECT *\nFROM view\nLIMIT 20, 10",
            (string) Database::get()->query(...['view'])->limit(10, 20)
        );

        self::assertSame(
            "SELECT *\nFROM view\nORDER BY c1 ASC",
            (string) Database::get()->query(...['view'])->orderBy('c1')
        );

        self::assertSame(
            "SELECT *\nFROM view\nORDER BY c1 ASC",
            (string) Database::get()->query(...['view'])->orderBy('c1')
        );

        self::assertSame(
            "SELECT *\nFROM view\nORDER BY c1 ASC, c2 DESC, c3 FOO",
            (string) Database::get()->query(...['view'])->orderBy('c1', ['c2' => 'DESC'], ['c3' => 'FOO'])
        );

        self::assertSame(
            "SELECT OXID\nFROM oxv_oxcategories_de\nWHERE (oxactive = '1')\nORDER BY oxleft ASC",
            (string) Database::get()->query(...[function (Model\Category $category) {
            }, ['oxactive' => 1]])->orderBy('oxleft')
        );
    }

    public function testJsonSerialize(): void
    {
        $map = Database::get()->query(...[T::COUNTRY, function (Row $row) {
            $row();
            $row('c');
            $row(['c']);
            $row(['c' => 'a']);
            $row('k', 'v');
            return $row(T\COUNTRY::ISOALPHA2, T\COUNTRY::ISOALPHA3);
        }, [T\COUNTRY::ISOALPHA2 => ['IN', ['DE', 'CH', 'NO']]]])->orderBy(T\COUNTRY::ISOALPHA2);

        self::assertEquals(json_encode(['CH' => 'CHE', 'DE' => 'DEU', 'NO' => 'NOR']), json_encode($map));
    }
}
