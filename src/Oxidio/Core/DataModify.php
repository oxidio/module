<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core;

use Php;
use Generator;
use Oxidio;
use PDO;

/**
 * @method callable insert(...$data)
 * @method callable update($data, ...$where)
 * @method callable delete(...$where)
 * @method callable map(iterable $iterable, callable $factory)
 * @method callable replace(iterable|callable $records, string $key, string ...$keys)
 */
class DataModify extends AbstractConditionalStatement
{
    /**
     * @var callable[]
     */
    private $observers;

    /**
     * @param string $view
     * @param callable ...$observers
     */
    public function __construct($view, callable ...$observers)
    {
        $this->properties['view'] = (string)$view;
        $this->observers = $observers;
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

        foreach (Php::isCallable($data) ? $data($this) : $data as $column => $value) {
            if (is_iterable($value)) {
                continue;
            }
            if (Php::isCallable($value)) {
                if (!is_array($result = $value($column))) {
                    // dynamic value, e.g. UPPER(:column)
                    $bindings[$column] = $result;
                    continue;
                }

                $binding = key($result);
                $value = current($result);

            } else {
                $binding = ":$column";
            }
            $bindings[$column] = $binding;

            $values[$column] = $value;
            if (is_bool($value)) {
                $types[$column] = PDO::PARAM_BOOL;
            } elseif ($value === null) {
                $types[$column] = PDO::PARAM_NULL;
            }
        }

        return [$values, $types, $bindings];
    }

    /**
     * @uses _insert, _update, _delete, _map, _replace
     * @param string $method
     * @param array $args
     *
     * @return callable
     */
    public function __call(string $method, array $args): callable
    {
        $fn = $this->{"_$method"}(...$args);
        foreach ($this->observers as $observer) {
            $observer($fn, $this);
        }
        return $fn;
    }

    /**
     * @see DataModify::insert
     * @see \Doctrine\DBAL\Connection::insert
     *
     * INSERT INTO `view` (`c1`, `c2`) VALUES (:c1, ENCODE(:c2, 'phrase'))
     *
     * @param iterable|callable ...$data
     *
     * @return callable
     */
    protected function _insert(...$data): callable
    {
        return function (bool $dryRun = false) use ($data) {
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
     * @see DataModify::update
     * @see \Doctrine\DBAL\Connection::update
     *
     * UPDATE `view` SET `c1` = :c1, `c2` = ENCODE(:c2, 'phrase'), `c3` = UPPER(`c3`) WHERE `c4` = :c4
     *
     * @param iterable|callable $data
     * @param array ...$where
     *
     * @return callable
     */
    protected function _update($data, ...$where): callable
    {
        return function (bool $dryRun = false) use ($data, $where) {
            $this->where(...$where);
            [$values, $types, $bindings] = $this->convertData($data);

            $set = Php::map($bindings, static function ($binding, $column) {
                return "$column = $binding";
            })->string(",\n  ");

            $sql = "UPDATE {$this->view} SET\n  {$set}{$this}";

            return [$sql => $dryRun ? 0 : ($this->db)($sql, $values, $types)];
        };
    }

    /**
     * @see DataModify::delete
     * @see \Doctrine\DBAL\Connection::delete
     *
     * UPDATE FROM `view` WHERE `c3` = :c3
     *
     * @param array ...$where
     *
     * @return callable
     */
    protected function _delete(...$where): callable
    {
        return function (bool $dryRun = false) use ($where) {
            $this->where(...$where);
            $sql = "DELETE FROM {$this->view}{$this}";
            return [$sql => $dryRun ? 0 : ($this->db)($sql)];
        };
    }

    /**
     * @see DataModify::map
     *
     * @param iterable $iterable
     * @param callable $factory
     *
     * @return callable
     */
    protected function _map(iterable $iterable, callable $factory): callable
    {
        return function (bool $dryRun = false) use ($iterable, $factory): Generator {
            $observers = $this->observers;
            $this->observers = [];
            foreach ($iterable as $key => $value) {
                foreach ($factory($this, $value, $key) as $callback) {
                    yield $callback($dryRun);
                }
            }
            $this->observers = $observers;
        };
    }

    /**
     * @see DataModify::replace
     *
     * delete by primary key: ->replace($records, 'p1')
     * yield 'P1-num' => null
     * delete by unique index: ->replace($records)
     * yield ['u1' => '1', 'u2' => '2a'] => null
     * yield ['u1' => '1', 'u2' => '2b'] => null
     *
     * INSERT INTO `view` (
     *   `p1`, `c1`, `c2`
     * ) VALUES (
     *   :p1, :c1, ENCODE(:c2, 'phrase')
     * ) ON DUPLICATE KEY UPDATE
     *   `c1` = VALUES(c1),
     *   `c2` = VALUES(c2)
     *
     * merge by primary keys: ->replace($records, 'p1')
     * yield ['p1' => 'P1', 'c1' => '1', 'c2' => '1']
     * yield 'P1-num' => ['c1' => '2', 'c2' => '2']
     *
     *
     * INSERT INTO `view` (
     *   `p1`, `p2`, `u1`, `u2`, `c1`, `c2`
     * ) SELECT `p1`, `p2`, `u1`, `u2`, `c1`, `c2` FROM  (
     *   SELECT 1 `_`, `p1`, `p2`, `u1`, `u2`, `c1`, `c2` FROM `view` WHERE `u1` = :u1 AND `u2` = :u2
     *   UNION
     *   SELECT 2 `_`, :p1, :p2, :u1, :u2, :c1, :c2
     * ) `_` ORDER BY `_` LIMIT 1
     * ON DUPLICATE KEY UPDATE
     *   `c1` = :c1,
     *   `c2` = :c2
     *
     * merge by unique index: ->replace($records, 'p1', 'p2')
     * yield ['u1' => 'U1', 'u2' => 'U2-foo'] => ['p1' => 'P1', 'p2' => 'P2-foo', 'c1' => 'C1', 'c2' => 'C2-foo']
     * yield ['u1' => 'U1', 'u2' => 'U2-bar'] => ['p1' => 'P1', 'p2' => 'P2-bar', 'c1' => 'C1', 'c2' => 'C2-bar']
     *
     * @param iterable|callable $records
     * @param string ...$pks primary keys
     * @return callable
     */
    protected function _replace($records, string ...$pks): callable
    {
        return function (bool $dryRun = false) use ($records, $pks) {
            $result = [];
            foreach (Php::isCallable($records) ? $records($this) : $records as $id => $record) {
                $index = [];
                if (!is_iterable($id)) {
                    $pks && $index[$pks[0]] = $id;
                } else {
                    foreach ($id as $idKey => $idValue) {
                        if (is_numeric($idKey)) {
                            $index[$pks[$idKey]] = $idValue;
                        } else {
                            $index[$idKey] = $idValue;
                        }
                    }
                }
                [$values, $types, $bindings] = $this->convertData(($record ?? []) + $index);

                $terms = Php::arr(array_keys($index), function ($column) use ($bindings) {
                    yield "$column = {$bindings[$column]}";
                });              if ($record === null) {
                    $sql = "DELETE FROM {$this->view} WHERE " . implode(' AND ', $terms);
                    $count = $dryRun ? 0 : ($this->db)($sql, $values, $types);
                } else {
                    $colTerm = implode(', ', array_keys($bindings));
                    $valTerm  = implode(', ', $bindings);
                    if (!is_iterable($id)) {
                        $sql = implode("\n", [
                            "INSERT INTO {$this->view} (",
                            '  ' . $colTerm,
                            ') VALUES (',
                            '  ' . $valTerm,
                            ') ON DUPLICATE KEY UPDATE',
                            '  ' . implode(', ', Php::arr(array_keys($bindings), static function (string $column) use ($pks) {
                                Php::hasValue($column, $pks) || yield "$column = VALUES($column)";
                            })),
                        ]);
                    } else {
                        $sql = implode("\n", [
                            "INSERT INTO {$this->view} (",
                            '  ' . $colTerm,
                            ") SELECT $colTerm FROM (",
                            "  SELECT 1 `_`, $colTerm FROM {$this->view} WHERE " . implode(' AND ', $terms),
                            '  UNION',
                            "  SELECT 2 `_`, $valTerm",
                            ') `_` ORDER BY `_` LIMIT 1',
                            'ON DUPLICATE KEY UPDATE',
                            '  ' . implode(', ', Php::arr($bindings, static function (string $binding, string $column) use ($pks, $index) {
                                Php::hasValue($column, $pks) || isset($index[$column]) || yield "$column = $binding";
                            })),
                        ]);
                    }
                    $count = $dryRun ? 0 : ($this->db)($sql, $values, $types);
                }
                isset($result[$sql]) || $result[$sql] = 0;
                $result[$sql] += $count;
            }
            return $result;
        };
    }
}
