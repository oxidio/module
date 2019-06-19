<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio;

use OxidEsales\Eshop\Core\{
    Database\TABLE
};

/**
 * Test dml functionality
 *
 * @param callable $write
 * @param string|null $action
 * @param string|null $name
 * @param string|null $shop
 * @param bool $dryRun
 */
return static function (callable $write, string $action = null, string $name = null, string $shop = null, bool $dryRun = false) {
    $where = [TABLE\OXCONFIG\OXID => "test:$name"];
    $modify = shop($shop)->modify(TABLE\OXCONFIG);

    if ($action === 'insert') {
        $write($modify->insert([
            TABLE\OXCONFIG\OXMODULE => 'test',
            TABLE\OXCONFIG\OXVARNAME => $name,
            TABLE\OXCONFIG\OXID => "test:$name",
        ])($dryRun));
    } else if ($action === 'update') {
        $write($modify->update([
            TABLE\OXCONFIG\OXTIMESTAMP => null,
        ], $where)($dryRun));
    } else if ($action === 'delete') {
        $write($modify->delete($where)($dryRun));
    } else {
        $write(shop($shop)->query(TABLE\OXCONFIG, [TABLE\OXCONFIG\OXID => ['LIKE', 'test:%']]));
    }
};
