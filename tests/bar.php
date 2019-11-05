#!/usr/bin/env php
<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

call_user_func(function() {
    $file = file_exists($file = __DIR__ . '/../../../autoload.php') ? $file :  __DIR__ . '/../vendor/autoload.php';
    /** @noinspection PhpIncludeInspection */
    exit(call_user_func(require $file, static function () {
        return Oxidio\Module\Module::instance(Php\VENDOR\OXIDIO\MODULE_BAR)->cli->run();
    }));
});
