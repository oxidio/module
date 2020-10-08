<?php declare(strict_types=1);
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\DI;

use Php;

class Factory
{
    public static function value($value)
    {
        return $value;
    }

    public static function property($object, string $property)
    {
        return $object->$property;
    }

    public static function variadic(string $class, iterable $args)
    {
        $args = Php::arr($args);
        return new $class(...$args);
    }
}
