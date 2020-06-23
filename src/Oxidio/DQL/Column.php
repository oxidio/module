<?php declare(strict_types=1);
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\DQL;

use Php\PropertiesTrait\ReadOnly;

/**
 * @property-read string $alias = null
 * @property-read string $name
 */
class Column
{
    use ReadOnly;

    public function __construct($name, string $alias = null)
    {
        $this->properties = ['name' => $name, 'alias' => $alias];
    }

    public function __toString()
    {
        return $this->alias ?: $this->name;
    }
}
