<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio;

use php;
use Generator;

/**
 * Show/modify shop modules
 *
 * @param Core\Shop $shop
 * @param bool $commit
 * @param bool $invert
 * @param string $status inactive|removed
 * @param array $modules
 * @return Generator
 */
return static function (
    Core\Shop $shop,
    bool $commit = false,
    bool $invert = false,
    string $status = '',
    ...$modules
): Generator {
    yield php\traverse($shop->modules, function (Core\Extension $module) use ($modules, $status, $invert) {
        return [
            'id' => $module->id,
            'version' => $module->version,
            'status:before' => $module->status,
            'status:after' => ($invert xor php\hasValue($module->id, $modules)) ?
                $module->status = $status :
                $module->status,
            'config' => json_encode(php\traverse($module->config), JSON_PRETTY_PRINT),
        ];
    });

    foreach ($shop->commit($commit) as $result) {
        yield new php\Cli\Renderable((object)$result, php\Cli\IO::VERBOSITY_VERBOSE);
    }
};
