<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core;

use Doctrine\DBAL\Connection;
use OxidEsales\Eshop\Core\Database\Adapter;

trait DatabaseProxyTrait
{
    /**
     * @var Adapter\Doctrine\Database|string
     */
    private $proxy = 'parent';

    protected function getConnection(): ?Connection
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    protected function getConnectionParameters()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    public function setConnectionParameters(array $connectionParameters)
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    public function connect()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    public function forceMasterConnection()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    public function forceSlaveConnection()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    public function closeConnection()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    public function setFetchMode($fetchMode)
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    public function getOne($query, $parameters = [])
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    public function getRow($query, $parameters = [])
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    public function getCol($query, $parameters = [])
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    public function getAll($query, $parameters = [])
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    public function select($query, $parameters = [])
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    public function selectLimit($query, $rowCount = -1, $offset = 0, $parameters = [])
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    public function execute($query, $parameters = [])
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    public function quote($value)
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    public function quoteArray($array)
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    public function quoteIdentifier($string)
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    public function metaColumns($table)
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    public function startTransaction()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    public function commitTransaction()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    public function rollbackTransaction()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    public function setTransactionIsolationLevel($level)
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    public function isRollbackOnly()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    public function isTransactionActive()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }

    public function getLastInsertId()
    {
        return call_user_func([$this->proxy, __FUNCTION__], ...func_get_args());
    }
}
