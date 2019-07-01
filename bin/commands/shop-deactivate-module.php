<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio;

use fn;

return static function($shop = null, bool $commit = false, ...$modules) {
    yield fn\traverse(shop($shop)->modules, function (Core\Extension $module) use ($modules) {
        return [
            'id' => $module->id,
            'version' => $module->version,
            'active:before' => json_encode($module->active),
            'active:after' => json_encode(fn\hasValue($module->id, $modules) ? $module->active = false :  $module->active)
        ];
    });

    foreach (shop($shop)->commit($commit) as $result) {
        yield fn\io((object)$result, fn\Cli\IO::VERBOSITY_VERBOSE);
    }
};
