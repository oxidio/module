<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core;

use Php;
use Doctrine\DBAL;
use OxidEsales\Eshop\Core\Database\Adapter;
use OxidEsales\Eshop\Core\DatabaseProvider;

/**
 * @property-read DBAL\Connection $conn
 * @property-read DBAL\Schema\Table[] $tables
 * @property-read DBAL\Schema\View[] $views
 * @property-read DBAL\Schema\Schema $schema
 */
class Database extends Adapter\Doctrine\Database implements DataModificationInterface
{
    /**
     * @see \Php\PropertiesTrait::propResolver
     * @uses resolveTables, resolveViews, resolveSchema, resolveConn
     */

    use Php\PropertiesTrait\ReadOnly;
    use DatabaseProxyTrait;

    /**
     * @var static[]
     */
    protected static array $instances = [];

    /**
     * @return string[]
     */
    public static function all(): array
    {
        return static::get()->conn->getSchemaManager()->listDatabases();
    }

    public static function get(string $url = null): self
    {
        $url = (string)$url;
        if (!isset(static::$instances[$url])) {
            static::$instances[$url] = $db = new static();
            if ($url) {
                $params = DBAL\DriverManager::getConnection(['url' => $url])->getParams();
                $db->setConnectionParameters(['default' => [
                    'databaseName' => $params['dbname'] ?? null,
                    'databaseHost' => $params['host'] ?? $params['databaseHost'] ?? getenv('DB_HOST'),
                    'databasePassword' => $params['password'] ?? $params['databasePassword'] ?? getenv('DB_PASSWORD'),
                    'databaseUser' => $params['user'] ?? $params['databaseUser'] ?? getenv('DB_USER'),
                    'databasePort' => $params['port'] ?? $params['databasePort'] ?? getenv('DB_PORT') ?: 3306,
                    'connectionCharset' => $params['charset'] ?? $params['connectionCharset'] ?? getenv('DB_CHARSET') ?: '',
                ]]);
                $db->connect();
            } else {
                $db->proxy = DatabaseProvider::getDb(self::FETCH_MODE_ASSOC);
            }
            $db->conn->getDatabasePlatform()->registerDoctrineTypeMapping('enum', DBAL\Types\Types::STRING);
            static::$instances[$url] = $db;
        }
        static::$instances[$url]->setFetchMode(self::FETCH_MODE_ASSOC);
        return static::$instances[$url];
    }

    /**
     * @see $conn
     */
    protected function resolveConn(): DBAL\Connection
    {
        return $this->getConnection();
    }

    /**
     * @see $schema
     */
    protected function resolveSchema(): DBAL\Schema\Schema
    {
        return $this->fix(function () {
            return $this->conn->getSchemaManager()->createSchema();
        })();
    }

    /**
     * @see $tables
     */
    protected function resolveTables(): array
    {
        return Php::traverse($this->schema->getTables(), function (DBAL\Schema\Table $table) {
            return Php::mapValue($table)->andKey($table->getName());
        });
    }

    /**
     * @see $views
     */
    protected function resolveViews(): array
    {
        return $this->conn->getSchemaManager()->listViews();
    }

    public function query($from = null, $mapper = null, ...$where): DataQuery
    {
        return (new DataQuery($from, $mapper, ...$where))->withDb($this->fix(function ($query, ...$args) {
            if ($query instanceof DataQuery && $query->orderTerms) {
                $it = new SelectStatementIterator($query, $this);
            } else {
                $it = $this->select((string)$query);
            }
            return new Php\Map($it, ...$args);
        }));
    }

    public function define(callable ...$version): DataDefine
    {
        return new DataDefine($this, $version);
    }

    public function modify($view, callable ...$observers): DataModify
    {
        return (new DataModify($view, ...$observers))->withDb($this->fix(function (...$args) {
            return $this->executeUpdate(...$args);
        }));
    }

    private function fix(callable $callable, int $mode = self::FETCH_MODE_ASSOC): callable
    {
        return function (...$args) use ($callable, $mode) {
            $pdoMode = is_object($this->proxy) ? $this->proxy->fetchMode : $this->fetchMode;
            $this->setFetchMode($mode);
            $result = $callable(...$args);
            is_object($this->proxy) ? $this->proxy->fetchMode = $pdoMode : $this->fetchMode = $pdoMode;
            return $result;
        };
    }
}
