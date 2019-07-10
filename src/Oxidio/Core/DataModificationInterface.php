<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core;

use Oxidio;

/**
 */
interface DataModificationInterface extends DataQueryInterface
{
    /**
     * @param string $view
     * @param callable ...$observers
     *
     * @return Modify
     */
    public function modify($view, callable ...$observers): Modify;
}
