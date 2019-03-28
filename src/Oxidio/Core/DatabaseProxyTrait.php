<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core;

use Doctrine\DBAL\Connection;

/**
 */
trait DatabaseProxyTrait
{
    /**
     * @var \OxidEsales\Eshop\Core\Database\Adapter\Doctrine\Database
     */
    private $proxy = 'parent';

    /**
     * @inheritdoc
     *
     * @return Connection
     */
    protected function getConnection(): ?Connection
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @inheritdoc
     */
    protected function getConnectionParameters()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function setConnectionParameters(array $connectionParameters)
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function connect()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function forceMasterConnection()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function forceSlaveConnection()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function closeConnection()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function setFetchMode($fetchMode)
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function getOne($query, $parameters = [])
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function getRow($query, $parameters = [])
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function getCol($query, $parameters = [])
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function getAll($query, $parameters = [])
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function select($query, $parameters = [])
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function selectLimit($query, $rowCount = -1, $offset = 0, $parameters = [])
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function execute($query, $parameters = [])
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function quote($value)
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function quoteArray($array)
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function quoteIdentifier($string)
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function metaColumns($table)
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function startTransaction()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function commitTransaction()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function rollbackTransaction()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function setTransactionIsolationLevel($level)
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function isRollbackOnly()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function isTransactionActive()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function getLastInsertId()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }


}
