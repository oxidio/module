<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */

namespace Oxidio
{
    use fn;
    use OxidEsales\Eshop;
    use Oxidio\Model\Query;

    /**
     * @param int $fetchMode
     *
     * @return Eshop\Core\Database\Adapter\DatabaseInterface
     */
    function db($fetchMode = Eshop\Core\DatabaseProvider::FETCH_MODE_ASSOC)
    {
        return Eshop\Core\DatabaseProvider::getDb($fetchMode);
    }

    /**
     * @param string $sql
     * @param callable ...$mapper
     *
     * @return fn\Map|array[]
     */
    function select($sql, callable ...$mapper)
    {
        return fn\map(db()->select((string)$sql), ...$mapper);
    }

    /**
     * @param mixed ...$args
     *
     * @return Query
     */
    function query(...$args): Query
    {
        return new Query(...$args);
    }
}

namespace Oxidio\Module
{
    function append($callable): Block
    {
        return new Block($callable, Block::APPEND);
    }

    function prepend($callable): Block
    {
        return new Block($callable, Block::PREPEND);
    }

    function overwrite($callable): Block
    {
        return new Block($callable, Block::OVERWRITE);
    }

}
