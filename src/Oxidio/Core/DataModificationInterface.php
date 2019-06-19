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
     *
     * @return Modify
     */
    public function modify($view): Modify;
}
