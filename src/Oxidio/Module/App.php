<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Module;

use OxidEsales\Eshop\Application\Controller\Admin\AdminController;
use OxidEsales\Eshop\Core\Registry;

/**
 */
class App extends AdminController
{
    protected $_sThisTemplate = APP;

    /**
     * @see Module::activate
     * @return string
     */
    public function renderApp(): string
    {
        [$id, $app] = explode(':', Registry::getRequest()->getRequestParameter(APP));
        return Module::instance($id)->renderApp($app);
    }
}
