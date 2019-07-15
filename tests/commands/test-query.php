<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio;

use fn;
use Generator;
use OxidEsales\Eshop\Core\{
    Database\TABLE
};
use Oxidio\Core\Row;

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
    string $from = TABLE\OXCONFIG,
    array $columns = [TABLE\OXCONFIG\OXID],
    array $order = [TABLE\OXCONFIG\OXID],
    int $limit = 0,
    int $start = 0
) {
    $query = $shop->query($from, function(array $row) use($columns, $shop) {

        // fails if PDO::MYSQL_ATTR_USE_BUFFERED_QUERY is disabled
        fn\traverse($shop->query(TABLE\OXSHOPS));

        if (!$columns) {
            return $row;
        }
        $row =  fn\traverse([new Row($row)], fn\mapRow($columns));
        return $row[0];

    })->orderBy($order)->limit($limit, $start);

    yield from $query;

    yield fn\io(fn\str('(%s) %s', $query->total, $query), fn\Cli\IO::VERBOSITY_VERBOSE);
};
