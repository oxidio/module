<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

if (file_exists(getcwd() . '/source/bootstrap.php')) {
    /** @noinspection PhpIncludeInspection */
    require_once getcwd() . '/source/bootstrap.php';
}
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/Oxidio/oxidio-functions.php';
