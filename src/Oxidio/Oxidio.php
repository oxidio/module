<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio;

use php;
use ReflectionClass;
use ReflectionException;

class Oxidio
{
    /**
     * @param string          $input
     * @param string|string[] $replacePrefix
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

    public static function constName(string $value, string $class): ?string
    {
        static $cache = [];
        if (!isset($cache[$class])) {
            try {
                $cache[$class] = array_flip((new ReflectionClass($class))->getConstants());
            } catch (ReflectionException $e) {
                $cache[$class] = [];
            }
        }
        return $cache[$class][$value] ?? null;
    }
}
