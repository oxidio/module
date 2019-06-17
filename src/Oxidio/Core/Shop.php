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

    public const CONFIG_KEY = 'fq45QS09_fqyx09239QQ';

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
        return $this->db->query(TABLE\OXCATEGORIES, function (Row $row) {
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

    /**
     * @param mixed ...$where
     *
     * @return fn\Map|array[]
     */
    public function config(...$where): fn\Map
    {
        $from = fn\str('(SELECT ' .
            fn\map([
                '{c.mod} module',
                '{c.var} name',
                '{c.type} type',
                "DECODE({c.val}, '{pass}') value",
                '{cd.gr} gr',
                '{cd.pos} pos',
            ])->string(', ') .
            ' FROM {c} LEFT JOIN {cd} ON {c.mod} = {cd.mod} AND {c.var} = {cd.var}) config',
            [
                'pass' => static::CONFIG_KEY,
                'c' => TABLE\OXCONFIG . ' c',
                'cd' => TABLE\OXCONFIGDISPLAY . ' cd',
                'c.mod' => 'c.' . TABLE\OXCONFIG\OXMODULE,
                'c.var' => 'c.' . TABLE\OXCONFIG\OXVARNAME,
                'c.val' => 'c.' . TABLE\OXCONFIG\OXVARVALUE,
                'c.type' => 'c.' . TABLE\OXCONFIG\OXVARTYPE,
                'cd.mod' => 'cd.' . TABLE\OXCONFIGDISPLAY\OXCFGMODULE,
                'cd.var' => 'cd.' . TABLE\OXCONFIGDISPLAY\OXCFGVARNAME,
                'cd.gr' => 'cd.' . TABLE\OXCONFIGDISPLAY\OXGROUPING,
                'cd.pos' => 'cd.' . TABLE\OXCONFIGDISPLAY\OXPOS,
            ]
        );
        return fn\map($this->db->query($from, ...$where)->orderBy('module', 'gr', 'pos', 'name'),
            static function(array $row) {
                strpos($row['type'] , 'rr') && $row['value'] = unserialize($row['value'], [null]);
                return fn\mapGroup((string)$row['module'])->andKey($row['name'])->andValue($row);
            }
        );
    }
}
