<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core;

use fn;
use Doctrine\DBAL;
use OxidEsales\Eshop\Core\Database\Adapter;
use OxidEsales\Eshop\Core\DatabaseProvider;
use PDO;

/**
 * @property-read DBAL\Schema\Table[] $tables
 * @property-read DBAL\Schema\Schema $schema
 */
class Database extends Adapter\Doctrine\Database implements DataModificationInterface
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
            $db->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', DBAL\Types\Type::STRING);
            if (($pdo = $db->getConnection()->getWrappedConnection()) instanceof PDO) {
                $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
            }
            static::$instances[$locator] = $db;
        }
        static::$instances[$locator]->setFetchMode($mode);
        return static::$instances[$locator];
    }

    /**
     * @inheritDoc
     */
    protected function propertyMethodInvoke(string $name)
    {
        if (!fn\hasKey($name, $this->properties)) {
            $this->properties[$name] = $this->{$this->propertyMethod($name)->name}();
        }
        return $this->properties[$name];
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
     * @inheritDoc
     */
    public function query($from = null, $mapper = null, ...$where): Query
    {
        $fetchMode = is_object($this->proxy) ? $this->proxy->fetchMode : $this->fetchMode;
        return (new Query($from, $mapper, ...$where))->withDb(function(...$args) use($fetchMode) {
            $temp = is_object($this->proxy) ? $this->proxy->fetchMode : $this->fetchMode;
            $this->setFetchMode($fetchMode);
            $map = $this(...$args);
            $this->setFetchMode($temp);
            return $map;
        });
    }

    /**
     * @inheritDoc
     */
    public function modify($view): Modify
    {
        $fetchMode = is_object($this->proxy) ? $this->proxy->fetchMode : $this->fetchMode;

        return (new Modify($view))->withDb(function(...$args) use($fetchMode) {
            $temp = is_object($this->proxy) ? $this->proxy->fetchMode : $this->fetchMode;
            $this->setFetchMode($fetchMode);
            $affected = $this->executeUpdate(...$args);
            $this->setFetchMode($temp);
            return $affected;
        });
    }
}
