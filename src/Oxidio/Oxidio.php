<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio;

use Oxidio\DI\Container;
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

    public static function constName($value, string $class): ?string
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

    public static function cast(array $row, array $booleans = [], array $numbers = []): array
    {
        foreach ($booleans as $key) {
            isset($row[$key]) && $row[$key] = (bool)$row[$key];
        }
        foreach ($numbers as $key) {
            isset($row[$key]) && $row[$key] = 0 + $row[$key];
        }
        return $row;
    }

    /**
     * @param string|iterable|null $name
     * @param mixed $default
     *
     * @return mixed|Container
     */
    public static function di($name = null, $default = null)
    {
        static $instance;
        if (!$instance) {
            $instance = Container::oe()->get(Container::class);
        }
        return $instance->di(...func_get_args());
    }

    public static function call($callable, array $params = [])
    {
        return static::di()->call(...func_get_args());
    }
}
