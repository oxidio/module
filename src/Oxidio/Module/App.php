<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Module;

use OxidEsales\Eshop\Application\Controller\Admin\AdminController;
use OxidEsales\Eshop\Core\Registry;

class App extends AdminController
{
    protected $_sThisTemplate = Module::APP;

    /**
     * @see Module::activate
     * @return string
     */
    public function renderApp(): string
    {
        [$id, $app] = explode(':', Registry::getRequest()->getRequestParameter(Module::APP));
        return Module::instance($id)->renderApp($app);
    }

    /**
     * @param mixed $label
     * @param callable $callable
     * @return Menu
     */
    public static function menu($label, $callable): Menu
    {
        $menu = new Menu($label);
        $menu->class = static::class;
        $menu->callback = $callable;
        $menu->params = [
            /* @see Module::params */
            Module::APP => function (Module $module, $menuKey) {
                return $module->id . ":{$menuKey}";
            }
        ];
        return $menu;
    }
}
