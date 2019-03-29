<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core;

use fn;
use Doctrine\DBAL;
use OxidEsales\Eshop\Core\Database\Adapter;
use OxidEsales\Eshop\Core\DatabaseProvider;

/**
 * @property-read DBAL\Schema\Table[] $tables
 * @property-read DBAL\Schema\Schema $schema
 */
class Database extends Adapter\Doctrine\Database
{
    use fn\PropertiesReadOnlyTrait;
    use DatabaseProxyTrait;

    /**
     * @var static[]
     */
    protected static $instances = [];

    /**
     * @return string[]
     */
    public static function all(): array
    {
        return static::get()->getConnection()->getSchemaManager()->listDatabases();
    }

    /**
     * @param int|string $locator
     * @param int        $mode
     *
     * @return static
     */
    public static function get($locator = null, int $mode = self::FETCH_MODE_ASSOC): self
    {
        $locator === null && $locator = $mode;
        if (!isset(static::$instances[$locator])) {
            static::$instances[$locator] = $db = new static;
            if (is_int($locator)) {
                $db->proxy = DatabaseProvider::getDb($locator);
            } else {
                if (strpos($locator, '://')) {
                    $params = DBAL\DriverManager::getConnection(['url' => $locator])->getParams();
                } else {
                    $params = ['dbname' => $locator] + static::get()->getConnectionParameters();
                }
                $db->setConnectionParameters(['default' => [
                    'databaseName'     => $params['dbname'] ?? null,
                    'databaseHost'     => $params['host'] ?? $params['databaseHost'] ?? getenv('DB_HOST'),
                    'databasePassword' => $params['password'] ?? $params['databasePassword'] ?? getenv('DB_PASSWORD'),
                    'databaseUser'     => $params['user'] ?? $params['databaseUser'] ?? getenv('DB_USER'),
                    'databasePort'     => $params['port'] ?? $params['databasePort'] ?? getenv('DB_PORT') ?: 3306,
                ]]);
                $db->connect();
            }
            $db->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
            static::$instances[$locator] = $db;
        }
        static::$instances[$locator]->setFetchMode($mode);
        return static::$instances[$locator];
    }

    /**
     * @inheritdoc
     */
    protected function property(string $name, bool $assert)
    {
        if (fn\hasKey($name, $this->properties)) {
            return $assert ? $this->properties[$name] : true;
        }
        if (method_exists($this, "resolve$name")) {
            return $assert ? $this->{"resolve$name"}() : true;
        }
        $assert && fn\fail($name);
        return false;
    }

    protected function resolveSchema(): DBAL\Schema\Schema
    {
        return $this->properties['schema'] = $this->getConnection()->getSchemaManager()->createSchema();
    }

    protected function resolveTables(): array
    {
        return $this->schema->getTables();
    }

    /**
     * @param string      $sql
     * @param callable ...$mapper
     *
     * @return fn\Map|array[]
     */
    public function __invoke($sql, callable ...$mapper): fn\Map
    {
        return fn\map($this->select((string)$sql), ...$mapper);
    }

    /**
     * @param callable|string $from
     * @param callable|array $mapper
     * @param array[] $where
     *
     * @return Query
     */
    public function query($from = null, $mapper = null, ...$where): Query
    {
        return (new Query($from, $mapper, ...$where))->withDb($this);
    }
}
