<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Bar\Cli;

use Doctrine\DBAL\Schema;
use fn;
use Oxidio;

class Db
{
    /**
     * @var Oxidio\Core\Database
     */
    private $db;

    public function __invoke(fn\Cli\IO $io, string $url = null, string $filter = null) {
        $this->db = Oxidio\db($url);
        $schema = $this->db->schema;
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

            $columns = fn\traverse($table->getColumns(), function(Schema\Column $column) {
                return $column->toArray();
            });
            $io->isVeryVerbose() && $io->table(fn\keys(reset($columns)), $columns);
        }

        $io->isDebug() && $io->listing($this->db::all());
    }

    protected function similar(Schema\Table $left): array
    {
        return fn\traverse((function() use($left) {
            foreach (Oxidio\Core\SimilarColumns::primary($left) as $name) {
                $similar = new Oxidio\Core\SimilarColumns($this->db, $left, $left->getColumn($name));
                foreach ($similar->queries() as $fqn => $query) {
                    ($total = $query->total) && yield "$name ~ {$fqn} ({$total})";
                }
            }
        })());
    }
}
