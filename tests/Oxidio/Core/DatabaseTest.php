<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core;

use Doctrine\DBAL;
use php\test\assert;
use php;
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
        assert\same([
            'CREATE TABLE t1 (c1 VARCHAR(255) NOT NULL, c2 VARCHAR(255) NOT NULL) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB' => true,
           "INSERT INTO t1 (\n  c2, c1\n) VALUES (\n  :c2, :c1\n) ON DUPLICATE KEY UPDATE\n  c2 = VALUES(c2)" => 0,
            'CREATE TABLE t2 (c1 VARCHAR(255) NOT NULL) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB' => true,
        ], php\traverse($up(true)));

        assert\type('callable', $down = $define->down());
        assert\same([
            'DROP TABLE t1' => true,
            'DROP TABLE t2' => true,
        ], php\traverse($down(true)));
    }
}
