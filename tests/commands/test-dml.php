<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio;

use Generator;
use OxidEsales\Eshop\Core\{
    Database\TABLE
};

/**
 * Test dml functionality
 *
 * @param Core\Shop $shop
 * @param string|null $action insert|update|delete
 * @param string|null $name
 * @param bool $dryRun
 *
 * @return Generator
 */
return static function (Core\Shop $shop, string $action = null, string $name = null, bool $dryRun = false) {
    $where = [TABLE\OXCONFIG\OXID => "test:$name"];
    $modify = $shop->modify(TABLE\OXCONFIG);

    if ($action === 'insert') {
        yield (object)$modify->insert([
            TABLE\OXCONFIG\OXMODULE => 'test',
            TABLE\OXCONFIG\OXVARNAME => $name,
            TABLE\OXCONFIG\OXID => "test:$name",
        ])($dryRun);
    } else if ($action === 'update') {
        yield (object)$modify->update([TABLE\OXCONFIG\OXTIMESTAMP => null], $where)($dryRun);
    } else if ($action === 'delete') {
        yield (object)$modify->delete($where)($dryRun);
    }

    yield (object)$shop->query(TABLE\OXCONFIG, [TABLE\OXCONFIG\OXID => ['LIKE', 'test:%']]);
};
