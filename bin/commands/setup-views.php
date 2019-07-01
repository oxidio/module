<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Cli;

use fn\Cli\IO;
use OxidEsales\Eshop\Core\{ConfigFile, DbMetaDataHandler, Registry};

/**
 * (Re)generate database views
 *
 * @param IO $io
 */
return static function (IO $io) {
    $status = (object)['updateViews' => false, 'noException' => false];
    register_shutdown_function(function ($status) use ($io) {
        if (!$status->updateViews || !$status->noException) {
            $io->error('There was an error while regenerating the views.');
        }

        if (!$status->noException) {
            $io->error('Please look at `oxideshop.log` for more details.');
        }

        if ($status->noException && !$status->updateViews) {
            $io->error('Please double check the state of database and configuration.');
        }
    }, $status);

    Registry::get(ConfigFile::class)->setVar('aSlaveHosts', null);
    $status->updateViews = oxNew(DbMetaDataHandler::class)->updateViews();
    $status->noException = true;
    $status->updateViews ? $io->success('ok') : $io->error('nok');
};
