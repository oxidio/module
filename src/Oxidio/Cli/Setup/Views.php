<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Cli\Setup;

use Doctrine\DBAL\Schema\Column;
use Php;
use Oxidio;
use OxidEsales\Eshop\Core\{ConfigFile, DbMetaDataHandler};

class Views
{
    /**
     * (Re)generate database views
     *
     * @param Php\Cli\IO $io
     * @param ConfigFile $file
     */
    public function __invoke(Php\Cli\IO $io, ConfigFile $file)
    {
        $status = (object)['updateViews' => false, 'noException' => false];
        register_shutdown_function(static function ($status, Php\Cli\IO $io) {
            if (!$status->updateViews || !$status->noException) {
                $io->error('There was an error while regenerating the views.');
            }

            if (!$status->noException) {
                $io->error('Please look at `oxideshop.log` for more details.');
            }

            if ($status->noException && !$status->updateViews) {
                $io->error('Please double check the state of database and configuration.');
            }

            $status->updateViews ? $io->success('ok') : $io->error('nok');

        }, $status, $io);

        $file->setVar('aSlaveHosts', null);
        $status->updateViews = oxNew(DbMetaDataHandler::class)->updateViews();
        $status->noException = true;
        $io->isVerbose() && self::verbose($io);
    }

    protected static function verbose(Php\Cli\IO $io): void
    {
        $db = Oxidio\Core\Database::get();

        $views = Php\keys($db->views, static function (string $view) {
            [, $table] = explode('_', $view);
            return Php::mapGroup($table);
        });

        $io->title($db->schema->getName());
        foreach ($db->tables as $name => $table) {
            $query = $db->query($name);

            $total = $io->isVeryVerbose() ? "<comment>total ({$query->total})</comment>" : '';
            $io->writeln(Php::str(
                '  * <info>%s</info> %s',
                $name,
                $total
            ));
            foreach ($views[$name] ?? [] as $view) {
                $io->writeln("    * $view");
            }

            $columns = Php\map($table->getColumns(), function (Column $column) {
                return $column->toArray();
            });
            $io->isDebug() && ($columns = Php\traverse($columns)) && $io->table(
                Php\keys(reset($columns)),
                $columns
            );
        }
    }
}
