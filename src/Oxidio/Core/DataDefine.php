<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core;

use Php;
use Generator;
use Oxidio;

/**
 */
class DataDefine
{
    /**
     * @var Database
     */
    private $db;

    /**
     * @var callable[]
     */
    private $versions;

    public function __construct(Database $db, iterable $versions)
    {
        $this->db = $db;
        $this->versions = $versions;
    }

    public function up(callable ...$observers): callable
    {
        return $this->diff(false, ...$observers);
    }

    public function down(callable ...$observers): callable
    {
        return $this->diff(true, ...$observers);
    }

    public function diff(bool $down, callable ...$observers): callable
    {
        return function (bool $dryRun = false) use ($down, $observers): Generator {
            $db = $this->db;
            $platform = $db->conn->getDatabasePlatform();
            $from = $db->schema;

            $dryRun || $db->startTransaction();
            try {
                foreach ($this->versions as $name => $version) {
                    $to = clone $from;
                    $result = $version($to, $db, $down);
                    $diff = $down ? $from->getMigrateFromSql($to, $platform) : $from->getMigrateToSql($to, $platform);
                    foreach ($observers as $observer) {
                        $observer($name, $diff);
                    }
                    foreach ($diff as $sql) {
                        $dryRun || $db->conn->executeQuery($sql);
                        yield $sql => $dryRun;
                    }
                    foreach (is_iterable($result) ? $result : [] as $sql => $params) {
                        if (Php\isCallable($params)) {
                            yield from $params($dryRun);
                        } else {
                            $count = $dryRun ? 0 : $db->executeUpdate($sql, ...$params);
                            yield $sql => $count;
                        }
                    }
                    $from = $to;
                }
                $dryRun || $db->commitTransaction();
            } catch (\Throwable $e) {
                $dryRun || $db->rollbackTransaction();
                throw $e;
            }
        };
    }
}
