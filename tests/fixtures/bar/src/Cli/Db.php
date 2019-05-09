<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Bar\Cli;

use Doctrine\DBAL\Schema;
use fn;
use Oxidio;

/**
 */
class Db
{
    /**
     * @var Oxidio\Core\Database
     */
    private $db;

    /**
     * show database information
     *
     * @param fn\Cli\IO   $io
     * @param string|null $db
     * @param string|null $filter
     */
    public function __invoke(fn\Cli\IO $io, string $db = null, string $filter = null)
    {
        $db && $db = static::urls()[$db] ?? $db;
        $this->db = Oxidio\db($db);
        $schema   = $this->db->schema;
        $io->title($schema->getName());
        foreach ($schema->getTables() as $table) {
            if ($filter && stripos($table->getName(), $filter) === false) {
                continue;
            }
            $query = $this->db->query($table->getName());

            $total = $io->isVerbose() ? "total ({$query->total})" : '';
            $io->section(fn\str(
                '%s [%s] %s',
                $table->getName(),
                fn\map(Oxidio\Core\SimilarColumns::primary($table))->string(','),
                $total
            ));
            $io->isVerbose() && $io->listing($this->similar($table));

            $columns = fn\traverse($table->getColumns(), function (Schema\Column $column) {
                return $column->toArray();
            });
            $io->isVeryVerbose() && $io->table(fn\keys(reset($columns)), $columns);
        }

        $io->isDebug() && $io->listing($this->db::all());
    }

    public static function urls(): fn\Map
    {
        return fn\map($_ENV, function ($url, &$var) {
            if (strpos($var, 'DB_URL_') !== 0) {
                return null;
            }
            $var = str_replace('_', '-', strtolower(substr($var, 7)));
            return $url;
        });
    }

    protected function similar(Schema\Table $left): array
    {
        return fn\traverse((function () use ($left) {
            foreach (Oxidio\Core\SimilarColumns::primary($left) as $name) {
                $similar = new Oxidio\Core\SimilarColumns($this->db, $left, $left->getColumn($name));
                foreach ($similar->queries() as $fqn => $query) {
                    ($total = $query->total) && yield "$name ~ {$fqn} ({$total})";
                }
            }
        })());
    }
}
