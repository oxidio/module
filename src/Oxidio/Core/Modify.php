<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core;

use php;
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
class Modify extends AbstractConditionalStatement
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

        foreach (php\isCallable($data) ? $data($this) : $data as $column => $value) {
            if (is_iterable($value)) {
                continue;
            }
            if (php\isCallable($value)) {
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
     * @see Modify::insert
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
     * @see Modify::update
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

            $set = php\map($bindings, static function ($binding, $column) {
                return "$column = $binding";
            })->string(",\n  ");

            $sql = "UPDATE {$this->view} SET\n  {$set}{$this}";

            return [$sql => $dryRun ? 0 : ($this->db)($sql, $values, $types)];
        };
    }

    /**
     * @see Modify::delete
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
     * @see Modify::map
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
     * @see Modify::replace
     *
     * INSERT INTO `view` (
     *   `key`, `c1`, `c2`
     * ) VALUES (
     *   :key, :c1, ENCODE(:c2, 'phrase')
     * ) ON DUPLICATE KEY UPDATE
     *   `c1` = VALUES(c1),
     *   `c2` = VALUES(c2)
     *
     * @param iterable|callable $records
     * @param string $key
     *
     * @return callable
     */
    protected function _replace($records, string $key): callable
    {
        return function (bool $dryRun = false) use ($records, $key) {
            $result = [];
            foreach (php\isCallable($records) ? $records($this) : $records as $id => $record) {
                if ($record === null) {
                    $this->where([$key => $id]);
                    $sql = "DELETE FROM {$this->view}{$this}";
                    $count = $dryRun ? 0 : ($this->db)($sql);
                } else {
                    [$values, $types, $bindings] = $this->convertData($record + [$key => $id]);
                    $sql = implode("\n", [
                        "INSERT INTO {$this->view} (",
                        '  ' . implode(', ', array_keys($bindings)),
                        ') VALUES (',
                        '  ' . implode(', ', $bindings),
                        ') ON DUPLICATE KEY UPDATE',
                        '  ' . implode(', ', php\keys($bindings, static function (string $column) use($key) {
                            return $column === $key ? null : "$column = VALUES($column)";
                        })),
                    ]);
                    $count = $dryRun ? 0 : ($this->db)($sql, $values, $types);
                }

                isset($result[$sql]) || $result[$sql] = 0;
                $result[$sql] += $count;
            }
            return $result;
        };
    }

}
