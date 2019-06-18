<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core;

use Oxidio;

/**
 */
interface DataQueryInterface
{
    /**
     * @param callable|string $from
     * @param callable|array $mapper
     * @param array[] $where
     *
     * @return Query
     */
    public function query($from = null, $mapper = null, ...$where): Query;
}
