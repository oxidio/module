<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core;

use Doctrine\DBAL;
use Php\test\assert;
use Php;
use Oxidio;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Database
 */
class DatabaseTest extends TestCase
{
    public function testDefine(): void
    {
        $define = Oxidio\db()->define(
            static function (DBAL\Schema\Schema $schema, Database $db, bool $down) {
                $t1 = $schema->createTable('t1');
                $t1->addColumn('c1', DBAL\Types\Type::STRING);
                $t1->addColumn('c2', DBAL\Types\Type::STRING);
                return $down ? [] : [
                    $db->modify('t1')->replace(['foo' => ['c2' => 'foo'], 'bar' => ['c2' => 'bar']], 'c1')
                ];
            },
            static function (DBAL\Schema\Schema $schema) {
                $schema->createTable('t2')->addColumn('c1', DBAL\Types\Type::STRING);
            }
        );
        assert\type(DataDefine::class, $define);
        assert\type('callable', $up = $define->up());
        assert\same(
            "INSERT INTO t1 (\n  c2, c1\n) VALUES (\n  :c2, :c1\n) ON DUPLICATE KEY UPDATE\n  c2 = VALUES(c2)",
            Php\map($up(true))->keys[1]
        );

        assert\type('callable', $down = $define->down());
        assert\same([
            'DROP TABLE t1' => true,
            'DROP TABLE t2' => true,
        ], Php\traverse($down(true)));
    }
}
