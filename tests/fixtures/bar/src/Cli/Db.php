<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Bar\Cli;

use Doctrine\DBAL\Schema;
use php;
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
     * @param php\Cli\IO $io
     * @param string|null $filter
     */
    public function __invoke(Oxidio\Core\Database $db, php\Cli\IO $io, string $filter = null)
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
            $io->section(php\str(
                '%s [%s] %s',
                $table->getName(),
                php\map(Oxidio\Core\SimilarColumns::primary($table))->string(','),
                $total
            ));
            $io->isVerbose() && $io->listing($this->similar($table));

            $columns = php\traverse($table->getColumns(), function (Schema\Column $column) {
                return $column->toArray();
            });
            $io->isVeryVerbose() && $io->table(php\keys(reset($columns)), $columns);
        }

        $io->isDebug() && $io->listing($this->db::all());
    }

    protected function similar(Schema\Table $left): array
    {
        return php\traverse((function () use ($left) {
            foreach (Oxidio\Core\SimilarColumns::primary($left) as $name) {
                $similar = new Oxidio\Core\SimilarColumns($this->db, $left, $left->getColumn($name));
                foreach ($similar->queries() as $fqn => $query) {
                    ($total = $query->total) && yield "$name ~ {$fqn} ({$total})";
                }
            }
        })());
    }
}
