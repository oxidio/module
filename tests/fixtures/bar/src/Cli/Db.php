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
                fn\map(self::primary($table))->string(','),
                $total
            ));
            $io->isVerbose() && $io->listing($this->relations($table));

            $columns = fn\traverse($table->getColumns(), function(Schema\Column $column) {
                return $column->toArray();
            });
            $io->isVeryVerbose() && $io->table(fn\keys(reset($columns)), $columns);
        }

        $io->isDebug() && $io->listing($this->db::all());
    }

    private static function primary(Schema\Table $table): array
    {
        return $table->hasPrimaryKey() ? $table->getPrimaryKeyColumns() : [];
    }

    private static function similar(Schema\Column $left, Schema\Column $right): bool
    {
        if ($left->getLength() !== $right->getLength()) {
            return false;
        }
        return (string) $left->getType() === (string) $right->getType();
    }

    protected function relations(Schema\Table $left): array
    {
        return fn\traverse((function() use($left) {
            foreach (self::primary($left) as $name) {
                $leftColumn = $left->getColumn($name);
                foreach ($this->db->tables as $right) {
                    $foreign = self::primary($right);
                    foreach ($right->getColumns() as $rightColumn) {
                        if (fn\hasValue($rightColumn->getName(), $foreign)) {
                            continue;
                        }
                        if (self::similar($leftColumn, $rightColumn)) {
                            yield "$name ~ {$rightColumn->getFullQualifiedName($right->getName())}";
                        }
                    }
                }
            }
        })());
    }
}
