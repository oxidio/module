<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

call_user_func(static function(...$files) {
    foreach ($files as $file) {
        if (file_exists($file)) {
            /** @noinspection PhpIncludeInspection */
            require_once $file;
        }
    }
},
    __DIR__ . '/../source/bootstrap.php',
    __DIR__ . '/../../../../source/bootstrap.php'
);

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/Oxidio/oxidio-functions.php';
