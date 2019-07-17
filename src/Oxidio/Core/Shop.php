<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core;

use fn;
use Oxidio;
use Generator;
use OxidEsales\Eshop\Application\Model\Category;
use OxidEsales\Eshop\Core\Database\TABLE;

/**
 * @property-read string $configKey
 * @property-read string $id
 * @property-read array $config
 * @property-read fn\Map|Extension[] $modules
 * @property-read fn\Map|Extension[] $themes
 * @property-read Database $db
 */
class Shop implements DataModificationInterface
{
    use fn\PropertiesTrait\ReadOnly;

    /**
     * @var string
     */
    public const CATEGORY_ROOT = 'oxrootid';

    /**
     * @var string
     */
    protected const DEFAULT_CONFIG_KEY = 'fq45QS09_fqyx09239QQ';

    /**
     * @var int|string
     */
    protected const DEFAULT_ID = 1;

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
     * @var bool
     */
    private $dirty = false;

    protected $transaction = [];

    /**
     * @param Database $db
     * @param array $params
     */
    public function __construct(Database $db, array $params = [])
    {
        $this->properties = ['db' => $db] + $params + ['configKey' => self::DEFAULT_CONFIG_KEY, 'locator' => null];
    }

    public function __toString()
    {
        return (string)($this->locator ?? '');
    }

    /**
     * @param array $where
     *
     * @return Query|Row[]
     */
    public function categories($where = [Category\PARENTID => self::CATEGORY_ROOT]): Query
    {
        return $this->query(TABLE\OXCATEGORIES, function (Row $row) {
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
     * @inheritDoc
     */
    public function query($from = null, $mapper = null, ...$where): Query
    {
        return $this->db->query($from, $mapper, ...$where);
    }

    /**
     * @inheritDoc
     */
    public function modify($view, callable ...$observers): Modify
    {
        return $this->db->modify($view, function (callable $action) {
            $this->transaction[] = $action;
        }, ...$observers);
    }

    public function save(): bool
    {
        if ($dirty = $this->dirty) {
            return $dirty;
        }

        $this->dirty = true;
        $table = $this->modify(TABLE\OXCONFIG);
        $table->map($this->modulesConfig(), function (Modify $table, $value, $name) {
            yield $table->update([
                TABLE\OXCONFIG\OXVARVALUE => function ($column) use ($value) {
                    return ["ENCODE(:$column, '{$this->configKey}')" => serialize($value)];
                },
            ], [
                TABLE\OXCONFIG\OXMODULE => Extension::SHOP,
                TABLE\OXCONFIG\OXSHOPID => $this->id,
                TABLE\OXCONFIG\OXVARNAME => $name
            ]);
        });

        $table->map($this->modules, function (Modify $table, Extension $module) {
            if ($module->id && $module->status === $module::STATUS_REMOVED) {
                yield $table->delete([
                    TABLE\OXCONFIG\OXSHOPID => $this->id,
                    TABLE\OXCONFIG\OXMODULE => "{$module->type}:{$module->id}",
                ], [
                    TABLE\OXCONFIG\OXSHOPID => $this->id,
                    TABLE\OXCONFIG\OXMODULE => $module->id,
                ]);
            }
        });

        return $dirty;
    }

    public function commit(bool $commit = true): Generator
    {
        $transaction = $this->transaction;
        $this->transaction = [];
        foreach ($transaction as $modify) {
            if (($modified = $modify(!$commit)) instanceof Generator) {
                yield from $modified;
            } else {
                yield $modified;
            }
        }
    }

    /**
     * @return array
     */
    protected function resolveExtensions(): array
    {
        return fn\traverse(Extension::all($this), static function (Extension $extension) {
            return fn\mapGroup($extension->type)->andKey($extension->id)->andValue($extension);
        });
    }

    /**
     * @see $modules
     * @return fn\Map
     */
    protected function resolveModules(): fn\Map
    {
        return fn\map($this->extensions[Extension::MODULE] ?? []);
    }

    /**
     * @see $themes
     * @return fn\Map
     */
    protected function resolveThemes(): fn\Map
    {
        return fn\map($this->extensions[Extension::THEME] ?? []);
    }

    /**
     * @see $config
     * @return mixed
     */
    protected function resolveConfig()
    {
        return $this->extensions[Extension::SHOP][Extension::SHOP]->config ?? [];
    }

    /**
     * @see $id
     * @return mixed
     */
    protected function resolveId()
    {
        return $this->query(TABLE\OXSHOPS, function($id) {
            return $id;
        })->orderBy(TABLE\OXSHOPS\OXID)->limit(1)[0] ?? static::DEFAULT_ID;
    }

    protected function modulesConfig(): Generator
    {
        yield 'aDisabledModules' => fn\map($this->modules, function(Extension $module) {
            return $module->status === $module::STATUS_INACTIVE ? $module->id : null;
        })->sort()->values;

        foreach (Extension::CONFIG_KEYS as $key => $property) {
            yield $key => fn\map($this->modules, function(Extension $module) use($property) {
                return $module->status === $module::STATUS_ACTIVE && $module->$property ? $module->$property : null;
            })->sort(fn\Map\Sort::KEYS)->traverse;
        }
    }
}
