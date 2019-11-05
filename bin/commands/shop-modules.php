<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio;

use Php;
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
    yield Php\traverse($shop->modules, function (Core\Extension $module) use ($modules, $status, $invert) {
        return [
            'id' => $module->id,
            'version' => $module->version,
            'status:before' => $module->status,
            'status:after' => ($invert xor Php\hasValue($module->id, $modules)) ?
                $module->status = $status :
                $module->status,
            'config' => json_encode(Php\traverse($module->config), JSON_PRETTY_PRINT),
        ];
    });

    foreach ($shop->commit($commit) as $result) {
        yield new Php\Cli\Renderable((object)$result, Php\Cli\IO::VERBOSITY_VERBOSE);
    }
};
