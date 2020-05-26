#!/usr/bin/env php
<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

call_user_func(function() {
    $file = file_exists($file = __DIR__ . '/../../../autoload.php') ? $file :  __DIR__ . '/../vendor/autoload.php';
    /** @noinspection PhpIncludeInspection */
    exit(call_user_func(require $file, static function () {
        $di = Php\Cli::di(
            Php\VENDOR\OXIDIO\MODULE_BAR,
            Oxidio::di()
        );
        $cli = new Php\Cli($di);

        $cli->command('bar', function (Php\Cli\IO $io) {
            $io->success('bar');
        });

        $cli->command('db', Oxidio\Bar\Cli\Db::class , ['filter']);
        $cli->command('shop', Oxidio\Bar\Cli\Shop::class);

        $cli->command('db:define', new Oxidio\Cli\Db\Define(static function () {
            yield 'bar:v1' => static function (Schema $schema) {
                $schema->createTable('bar')->addColumn('c1', Types::STRING);
            };

            yield 'bar:v2' => static function (Schema $schema) {
                $schema->getTable('bar')->addColumn('c2', Types::STRING);
            };

        }), ['filter']);

        return $cli->run();
    }));
});
