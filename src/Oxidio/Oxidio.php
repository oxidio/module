<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio;
use php;

class Oxidio
{
    /**
     * @param string $input
     * @param        $replacePrefix
     * @return string
     */
    public static function after(string $input, $replacePrefix): string
    {
        $replacePrefix = is_array($replacePrefix) ? $replacePrefix : [$replacePrefix => ''];
        foreach ($replacePrefix as $search => $replace) {
            if (stripos($input, $search) !== 0) {
                break;
            }
            $input = $replace . substr($input, strlen($search));
        }
        return $input;
    }

    public static function sanitize(string $input, string $suffix = '_'): string
    {
        return in_array(strtolower($input), php\Composer\DIPackages::RESERVED, true) ? $input . $suffix : $input;
    }
}
