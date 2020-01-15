<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Bar\Cli;

use Doctrine\DBAL\Schema;
use Php;
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
     * @param Oxidio\Core\Database $db
     * @param Php\Cli\IO           $io
     * @param string|null          $filter
     */
    public function __invoke(Oxidio\Core\Database $db, Php\Cli\IO $io, string $filter = null)
    {
        $this->db = $db;
        $schema   = $this->db->schema;
        $io->title($schema->getName());
        foreach ($schema->getTables() as $table) {
            if ($filter && stripos($table->getName(), $filter) === false) {
                continue;
            }
            $query = $this->db->query($table->getName());

            $total = $io->isVerbose() ? "total ({$query->total})" : '';
            $io->section(Php::str(
                '%s [%s] %s',
                $table->getName(),
                Php::map(Oxidio\Core\SimilarColumns::primary($table))->string(','),
                $total
            ));
            $io->isVerbose() && $io->listing($this->similar($table));

            $columns = Php::traverse($table->getColumns(), function (Schema\Column $column) {
                return $column->toArray();
            });
            $io->isVeryVerbose() && $io->table(Php::keys(reset($columns)), $columns);
        }

        $io->isDebug() && $io->listing($this->db::all());
    }

    protected function similar(Schema\Table $left): array
    {
        return Php::traverse((function () use ($left) {
            foreach (Oxidio\Core\SimilarColumns::primary($left) as $name) {
                $similar = new Oxidio\Core\SimilarColumns($this->db, $left, $left->getColumn($name));
                foreach ($similar->queries() as $fqn => $query) {
                    ($total = $query->total) && yield "$name ~ {$fqn} ({$total})";
                }
            }
        })());
    }
}
