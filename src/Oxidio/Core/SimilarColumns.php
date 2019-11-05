<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core;

use Php;
use Doctrine\DBAL\Schema;
use Generator;
use IteratorAggregate;

/**
 */
class SimilarColumns implements IteratorAggregate
{
    /**
     * @var Database
     */
    private $db;

    /**
     * @var Schema\Table
     */
    private $table;

    /**
     * @var Schema\Column
     */
    private $column;

    /**
     * @param Database      $db
     * @param Schema\Table  $table
     * @param Schema\Column $column
     */
    public function __construct(Database $db, Schema\Table $table, Schema\Column $column)
    {
        $this->db     = $db;
        $this->table  = $table;
        $this->column = $column;
    }

    /**
     * @return Schema\Table[]|Php\Map
     */
    public function getIterator(): Php\Map
    {
        return Php\map($this->db->tables, function(Schema\Table $table) {
            $similar = [];
            foreach ($table->getColumns() as $column) {
                if ($this->table->getName() === $table->getName() && $this->column->getName() === $column->getName()) {
                    continue;
                }
                self::similar($this->column, $column) && $similar[] = $column;
            }
            return new Schema\Table($table->getName(), $similar);
        });
    }

    /**
     * @return DataQuery[]|Generator
     */
    public function queries(): Generator
    {
        foreach ($this as $table) {
            foreach ($table->getColumns() as $column) {
                $from = "{$this->table->getName()} t0 INNER JOIN {$table->getName()} t1";
                $on   = "t0.{$this->column->getName()} = t1.{$column->getName()}";
                yield $column->getFullQualifiedName($table->getName()) => $this->db->query("$from ON $on");
            }
        }
    }

    public static function primary(Schema\Table $table): array
    {
        return $table->hasPrimaryKey() ? $table->getPrimaryKeyColumns() : [];
    }

    private static function similar(Schema\Column $left, Schema\Column $right): bool
    {
        if ($left->getLength() !== $right->getLength()) {
            return false;
        }
        if ($left->getPlatformOptions() !== $right->getPlatformOptions()) {
            return false;
        }

        return (string) $left->getType() === (string) $right->getType();
    }
}
