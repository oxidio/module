<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core;

use fn\test\assert;
use Oxidio;
use OxidEsales\Eshop\Application\Model;

/**
 */
class QueryTest extends \PHPUnit\Framework\TestCase
{
    public function testToString(): void
    {
        assert\same(
            "SELECT *\nFROM view",
            (string) Oxidio\query('view')
        );

        assert\same(
            "SELECT *\nFROM view\nWHERE (c1 = 'v1')",
            (string) Oxidio\query('view', ['c1' => 'v1'])
        );

        assert\same(
            "SELECT *\nFROM view\nWHERE (c1 > '0' AND c2 < '1') OR (c3 IS NULL AND c4 LIKE '%')",
            (string) Oxidio\query(
                'view',
                ['c1' => ['>', 0], 'c2' => ['<', 'value' => 1]],
                [['column' => 'c3', 'value' => null], 'c4' => ['value' => '%', 'op' => 'LIKE']]
            )
        );

        assert\same(
            "SELECT *\nFROM view",
            (string) Oxidio\query('view')->where()->limit(0)->orderBy()
        );

        assert\same(
            "SELECT *\nFROM view\nLIMIT 0, 1",
            (string) Oxidio\query('view')->limit(1)
        );

        assert\same(
            "SELECT *\nFROM view\nLIMIT 20, 10",
            (string) Oxidio\query('view')->limit(10, 20)
        );

        assert\same(
            "SELECT *\nFROM view\nORDER BY c1 ASC",
            (string) Oxidio\query('view')->orderBy('c1')
        );

        assert\same(
            "SELECT *\nFROM view\nORDER BY c1 ASC",
            (string) Oxidio\query('view')->orderBy('c1')
        );

        assert\same(
            "SELECT *\nFROM view\nORDER BY c1 ASC, c2 DESC, c3 FOO",
            (string) Oxidio\query('view')->orderBy('c1', ['c2' => 'DESC'], ['c3' => 'FOO'])
        );

        assert\same(
            "SELECT OXID\nFROM oxv_oxcategories_de\nWHERE (oxactive = '1')\nORDER BY oxleft ASC",
            (string) Oxidio\query(function(Model\Category $category) {}, ['oxactive' => 1])->orderBy('oxleft')
        );
    }
}
