<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio;

use php;
use Generator;
use Oxidio\Core\Row;
use Oxidio\Enum\Tables as T;

/**
 * Test query functionality
 *
 * @param Core\Shop $shop
 * @param string[] $columns
 * @param string $from
 * @param string[] $order
 * @param int $limit
 * @param int $start
 *
 * @return Generator
 */
return static function (
    Core\Shop $shop,
    string $from = T::CONFIG,
    array $columns = [T\CONFIG::ID],
    array $order = [T\CONFIG::ID],
    int $limit = 0,
    int $start = 0
) {
    $query = $shop->query($from, function(array $row) use($columns, $shop) {

        // fails if PDO::MYSQL_ATTR_USE_BUFFERED_QUERY is disabled
        php\traverse($shop->query(T::SHOPS));

        if (!$columns) {
            return $row;
        }
        $row =  php\traverse([new Row($row)], php\mapRow($columns));
        return $row[0];

    })->orderBy($order)->limit($limit, $start);

    yield from $query;

    yield php\io(php\str('(%s) %s', $query->total, $query), php\Cli\IO::VERBOSITY_VERBOSE);
};
