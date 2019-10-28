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
     * @var array
     */
    private const SEO_CHARS = [
        '&amp;' => '',
        '&quot;' => '',
        '&#039;' => '',
        '&lt;' => '',
        '&gt;' => '',
        'ä' => 'ae',
        'ö' => 'oe',
        'ü' => 'ue',
        'Ä' => 'AE',
        'Ö' => 'OE',
        'Ü' => 'UE',
        'ß' => 'ss',
    ];

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

    public static function seo($string, string $separator = '-', string $charset = 'UTF-8'): string
    {
        $string = html_entity_decode($string, ENT_QUOTES, $charset);
        $string = str_replace(array_keys(self::SEO_CHARS), array_values(self::SEO_CHARS), $string);
        return trim(
            preg_replace(['#/+#', "/[^A-Za-z0-9\\/$separator]+/", '# +#', "#($separator)+#"], $separator, $string),
            $separator
        );
    }

    public static function cast(array $row, string ...$booleans): array
    {
        foreach ($row as $key => $value) {
            if (is_numeric($value)) {
                $row[$key] = static::castValue($value, in_array($key, $booleans, true));
            }
        }
        return $row;
    }

    protected static function castValue($value, bool $bool = false)
    {
        if ($bool) {
            return (bool)$value;
        }
        return $value + 0;
    }
}
