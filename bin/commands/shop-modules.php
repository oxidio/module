<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio;

use fn;
use Generator;

/**
 * Show/modify shop modules
 *
 * @param string $shop e.g. prod|dev|test
 * @param bool $commit
 * @param bool $invert
 * @param string $status inactive|removed
 * @param array $modules
 * @return Generator
 */
return static function ($shop = null, bool $commit = false, bool $invert = false, string $status = '', ...$modules): Generator {
    yield fn\traverse(shop($shop)->modules, function (Core\Extension $module) use ($modules, $status, $invert) {
        return [
            'id' => $module->id,
            'version' => $module->version,
            'status:before' => $module->status,
            'status:after' => ($invert xor fn\hasValue($module->id, $modules)) ?
                $module->status = $status :
                $module->status
        ];
    });

    foreach (shop($shop)->commit($commit) as $result) {
        yield fn\io((object)$result, fn\Cli\IO::VERBOSITY_VERBOSE);
    }
};
