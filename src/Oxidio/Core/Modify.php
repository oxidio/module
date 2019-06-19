<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core;

use fn;
use Oxidio;
use PDO;

/**
 */
class Modify extends AbstractConditionalStatement
{
    /**
     * @param string $view
     */
    public function __construct($view)
    {
        $this->data['view'] = (string) $view;
    }

    /**
     * @param $data
     * @return array
     */
    protected function convertData($data): array
    {
        $types = [];
        $values = [];
        $bindings = [];

        foreach (fn\isCallable($data) ? $data($this) : $data as $column => $value) {
            $values[$column] = $value;
            if (is_bool($value)) {
                $types[$column] = PDO::PARAM_BOOL;
            } elseif ($value === null) {
                $types[$column] = PDO::PARAM_NULL;
            }
            $bindings[$column] = ":$column";
        }

        return [$values, $types, $bindings];
    }

    /**
     * @see \Doctrine\DBAL\Connection::insert
     *
     * INSERT INTO `view` (`c1`, `c2`) VALUES (:c1, :c2)
     *
     * @param iterable|callable ...$data
     *
     * @return callable
     */
    public function insert(...$data): callable
    {
        return function(bool $dryRun = false) use($data) {
            $result = [];
            foreach ($data as $row) {
                [$values, $types, $bindings] = $this->convertData($row);

                $columns = implode(', ', array_keys($bindings));
                $bindings = implode(', ', $bindings);

                $sql = "INSERT INTO {$this->view} (\n  {$columns}\n) VALUES (\n  {$bindings}\n)";
                $count = $dryRun ? 0 : ($this->db)($sql, $values, $types);

                isset($result[$sql]) || $result[$sql] = 0;
                $result[$sql] += $count;
            }
            return $result;
        };
    }

    /**
     * @see \Doctrine\DBAL\Connection::update
     *
     * UPDATE `view` SET `c1` = :c1, `c2` = :c2 WHERE `c3` = :c3
     *
     *
     * @param iterable|callable $data
     * @param array ...$where
     *
     * @return callable
     */
    public function update($data, ...$where): callable
    {
        return function (bool $dryRun = false) use ($data, $where) {
            $this->where(...$where);
            [$values, $types, $bindings] = $this->convertData($data);

            $set = fn\map($bindings, static function ($binding, $column) {
                return "$column = $binding";
            })->string(",\n  ");

            $sql = "UPDATE {$this->view} SET\n  {$set}{$this}";

            return [$sql => $dryRun ? 0 : ($this->db)($sql, $values, $types)];
        };
    }

    /**
     * @see \Doctrine\DBAL\Connection::delete
     *
     * UPDATE FROM `view` WHERE `c3` = :c3
     *
     * @param array ...$where
     *
     * @return callable
     */
    public function delete(...$where): callable
    {
        return function (bool $dryRun = false) use ($where) {
            $this->where(...$where);
            $sql = "DELETE FROM {$this->view}{$this}";
            return [$sql => $dryRun ? 0 : ($this->db)($sql)];
        };
    }
}
