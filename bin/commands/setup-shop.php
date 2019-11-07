<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Cli;

use Php;
use Php\Cli\IO;
use OxidEsales\EshopCommunity\Setup\{Dispatcher, Exception\SetupControllerExitException};

/**
 * Setup oxid shop
 *
 * @param IO $io
 * @param Dispatcher $dispatcher
 * @param string $action systemreq|welcome|license|dbinfo|dbconnect|dirsinfo|dirswrite|dbcreate|finish
 */
return static function (IO $io, Dispatcher $dispatcher, $action) {
    $setup = $dispatcher->getInstance('Setup');
    Php\some(Php\keys($setup->getSteps()), function (string $id) use ($action) {
        $method = str_replace('_', '', str_ireplace('step_', '', $id));

        return strtoupper($action) === $method;
    }) || Php::fail('unsupported $action %s', $action);

    $controller = $dispatcher->getInstance('Controller');
    $view = $controller->getView();
    try {
        $controller->$action();
    } catch (SetupControllerExitException $exception) {
    } finally {
        $io->title($view->getTitle());
    }
};
