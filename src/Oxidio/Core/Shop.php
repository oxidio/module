<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core;

use fn;
use OxidEsales\Eshop\Application\Model\Category;
use OxidEsales\Eshop\Core\Database\TABLE;

/**
 */
class Shop
{
    /**
     * @var string
     */
    public const CATEGORY_ROOT = 'oxrootid';

    /**
     * @var array
     */
    private const SEO_CHARS = [
        '&amp;'  => '',
        '&quot;' => '',
        '&#039;' => '',
        '&lt;'   => '',
        '&gt;'   => '',
        'ä'      => 'ae',
        'ö'      => 'oe',
        'ü'      => 'ue',
        'Ä'      => 'AE',
        'Ö'      => 'OE',
        'Ü'      => 'UE',
        'ß'      => 'ss',
    ];

    /**
     * @var Database
     */
    private $db;

    /**
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * @param array $where
     *
     * @return Query|Row[]
     */
    public function categories($where = [Category\PARENTID => self::CATEGORY_ROOT]): Query
    {
        return $this->db->query(TABLE\OXCATEGORIES, function(Row $row) {
            return fn\mapKey(static::seo($row[Category\TITLE]))->andValue(
                $row->withChildren($this->categories([Category\PARENTID => $row[Category\ID]]))
            );
        }, $where)->orderBy(Category\SORT);
    }

    /**
     * @param string $string
     * @param string $separator
     * @param string $charset
     * @return string
     */
    public static function seo($string, string $separator = '-', string $charset = 'UTF-8'): string
    {
        $string = html_entity_decode($string, ENT_QUOTES, $charset);
        $string = str_replace(array_keys(self::SEO_CHARS), array_values(self::SEO_CHARS), $string);
        return trim(
            preg_replace(['#/+#', "/[^A-Za-z0-9\\/$separator]+/", '# +#', "#($separator)+#"], $separator, $string),
            $separator
        );
    }
}
