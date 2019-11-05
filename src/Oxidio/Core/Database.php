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
    protected static $instances = [];

    /**
     * @return string[]
     */
    public static function all(): array
    {
        return static::get()->conn->getSchemaManager()->listDatabases();
    }

    /**
     * @param int|string $locator
     * @param int $mode
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
                    'databaseName' => $params['dbname'] ?? null,
                    'databaseHost' => $params['host'] ?? $params['databaseHost'] ?? getenv('DB_HOST'),
                    'databasePassword' => $params['password'] ?? $params['databasePassword'] ?? getenv('DB_PASSWORD'),
                    'databaseUser' => $params['user'] ?? $params['databaseUser'] ?? getenv('DB_USER'),
                    'databasePort' => $params['port'] ?? $params['databasePort'] ?? getenv('DB_PORT') ?: 3306,
                    'connectionCharset' => $params['charset'] ?? $params['connectionCharset'] ?? getenv('DB_CHARSET') ?: '',
                ]]);
                $db->connect();
            }
            $db->conn->getDatabasePlatform()->registerDoctrineTypeMapping('enum', DBAL\Types\Type::STRING);
            static::$instances[$locator] = $db;
        }
        static::$instances[$locator]->setFetchMode($mode);
        return static::$instances[$locator];
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
            $this->setFetchMode(self::FETCH_MODE_ASSOC);
            return $this->conn->getSchemaManager()->createSchema();
        })();
    }

    /**
     * @see $tables
     */
    protected function resolveTables(): array
    {
        return Php\traverse($this->schema->getTables(), function (DBAL\Schema\Table $table) {
            return Php\mapValue($table)->andKey($table->getName());
        });
    }

    /**
     * @see $views
     */
    protected function resolveViews(): array
    {
        return $this->conn->getSchemaManager()->listViews();
    }

    /**
     * @inheritDoc
     */
    public function query($from = null, $mapper = null, ...$where): DataQuery
    {
        return (new DataQuery($from, $mapper, ...$where))->withDb($this->fix(function ($mode, $query, ...$args) {
            $this->setFetchMode($mode);
            if ($query instanceof DataQuery && $query->orderTerms) {
                $it = new SelectStatementIterator($query, $this, $mode);
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

    /**
     * @inheritDoc
     */
    public function modify($view, callable ...$observers): DataModify
    {
        return (new DataModify($view, ...$observers))->withDb($this->fix(function ($mode, ...$args) {
            $this->fetchMode = $mode;
            return $this->executeUpdate(...$args);
        }));
    }

    public function fix(callable $callable)
    {
        $fetchMode = is_object($this->proxy) ? $this->proxy->fetchMode : $this->fetchMode;
        return function (...$args) use ($callable, $fetchMode) {
            $temp = is_object($this->proxy) ? $this->proxy->fetchMode : $this->fetchMode;
            $result = $callable($fetchMode, ...$args);
            $this->fetchMode = $temp;
            return $result;
        };
    }
}
