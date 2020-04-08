<?php declare(strict_types=1);
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core;

use Doctrine\DBAL;
use Php;
use Oxidio;
use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{
    public function testDefine(): void
    {
        $define = Database::get()->define(
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
        self::assertIsCallable($up = $define->up());
        self::assertSame(
            "INSERT INTO t1 (\n  c2, c1\n) VALUES (\n  :c2, :c1\n) ON DUPLICATE KEY UPDATE\n  c2 = VALUES(c2)",
            Php::map($up(true))->keys[1]
        );

        self::assertIsCallable($down = $define->down());
        self::assertSame([
            'DROP TABLE t1' => true,
            'DROP TABLE t2' => true,
        ], Php::arr($down(true)));
    }
}
