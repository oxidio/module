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
class Database extends Adapter\Doctrine\Database implements DataModificationInterface
{
    use fn\PropertiesTrait\ReadOnly;
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
            static::$instances[$locator] = $db;
        }
        static::$instances[$locator]->setFetchMode($mode);
        return static::$instances[$locator];
    }


    /**
     * @see $schema
     * @return DBAL\Schema\Schema
     */
    protected function resolveSchema(): DBAL\Schema\Schema
    {
        return $this->getConnection()->getSchemaManager()->createSchema();
    }

    /**
     * @see $tables
     * @return array
     */
    protected function resolveTables(): array
    {
        return fn\traverse($this->schema->getTables(), function(DBAL\Schema\Table $table) {
            return fn\mapValue($table)->andKey($table->getName());
        });
    }

    /**
     * @inheritDoc
     */
    public function query($from = null, $mapper = null, ...$where): Query
    {
        return (new Query($from, $mapper, ...$where))->withDb($this->fix(function($mode, $query, ...$args) {
            $this->setFetchMode($mode);
            if ($query instanceof Query && $query->orderTerms) {
                $it = new SelectStatementIterator($query, $this, $mode);
            } else {
                $it = $this->select((string) $query);
            }
            return new fn\Map($it, ...$args);
        }));
    }

    /**
     * @inheritDoc
     */
    public function modify($view, callable ...$observers): Modify
    {
        return (new Modify($view, ...$observers))->withDb($this->fix(function($mode, ...$args) {
            $this->setFetchMode($mode);
            return $this->executeUpdate(...$args);
        }));
    }

    public function fix(callable $callable)
    {
        $fetchMode = is_object($this->proxy) ? $this->proxy->fetchMode : $this->fetchMode;
        return function(...$args) use($callable, $fetchMode) {
            $temp = is_object($this->proxy) ? $this->proxy->fetchMode : $this->fetchMode;
            $result = $callable($fetchMode, ...$args);
            $this->setFetchMode($temp);
            return $result;
        };
    }
}
