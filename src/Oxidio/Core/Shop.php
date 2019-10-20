<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core;

use php;
use Oxidio;
use Generator;
use Oxidio\Enum\Tables as T;

/**
 * @property-read string $configKey
 * @property-read string $id
 * @property php\Map $config
 * @property-read php\Map|Extension[] $modules
 * @property-read php\Map|Extension[] $themes
 * @property-read Database $db
 */
class Shop implements DataModificationInterface
{
    /**
     * @see \php\PropertiesTrait::propResolver
     * @uses resolveId, resolveConfig, resolveModules, resolveThemes, resolveExtensions
     */

    use php\PropertiesTrait\ReadOnly;

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

    public function __invoke(...$args): self
    {
        foreach ($args as $arg) {
            $arg = php\isCallable($arg) ? $arg($this) : $arg;
            foreach (is_iterable($arg) ? $arg : [] as $view => $value) {
                $value = php\isCallable($value) ? $value($this) : $value;
                if (is_string($view)) {
                    $modify = $this->modify($view);
                    $value === null ? $modify->delete() : $modify->replace($value, T\SHOPS::ID);
                } else if (is_iterable($value)) {
                    php\traverse($value);
                }
            }
        }
        return $this;
    }

    public static function id(...$args): string
    {
        if (!$args) {
            return md5(uniqid('', true) . '|' . microtime());
        }
        if (false === ($last = array_pop($args))) {
            return implode('', $args);
        }
        is_string($last) && $args[] = $last;
        if (!is_string($last) || strlen(implode('', $args)) >= 32) {
            $args[] = substr(
                md5(uniqid('', true) . '|' . microtime()),
                0,
                is_int($last) ? $last : 6
            );
        }
        if (($diff = strlen($id = implode('', $args)) - 32) <= 0) {
            return $id;
        }
        $tokens = [];
        $avg = (int)ceil(32 / count($args));
        $diff += $avg * count($args) - 32;
        foreach (array_reverse($args) as $token) {
            $min = min($diff, max(0, ($length = strlen($token)) - $avg));
            $tokens[] = $token = substr($token, 0, $length - $min);
            $diff -= $length - strlen($token);
        }
        return implode('', array_reverse($tokens));
    }

    /**
     * @param array $where
     *
     * @return DataQuery|Row[]
     */
    public function categories($where = [T\CATEGORIES::PARENTID => self::CATEGORY_ROOT]): DataQuery
    {
        return $this->query(T::CATEGORIES, function (Row $row) {
            return php\mapKey(static::seo($row[T\CATEGORIES::TITLE]))->andValue(
                $row->withChildren($this->categories([T\CATEGORIES::PARENTID => $row[T\CATEGORIES::ID]]))
            );
        }, $where)->orderBy(T\CATEGORIES::SORT);
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
    public function query($from = null, $mapper = null, ...$where): DataQuery
    {
        return $this->db->query($from, $mapper, ...$where);
    }

    /**
     * @inheritDoc
     */
    public function modify($view, callable ...$observers): DataModify
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
        $table = $this->modify(T::CONFIG);
        $table->map($this->modulesConfig(), function (DataModify $table, $value, $name) {
            yield $table->update([
                T\CONFIG::VARVALUE => function ($column) use ($value) {
                    return ["ENCODE(:$column, '{$this->configKey}')" => serialize($value)];
                },
            ], [
                T\CONFIG::MODULE => Extension::SHOP,
                T\CONFIG::SHOPID => $this->id,
                T\CONFIG::VARNAME => $name
            ]);
        });

        $table->map($this->modules, function (DataModify $table, Extension $module) {
            if ($module->id && $module->status === $module::STATUS_REMOVED) {
                yield $table->delete([
                    T\CONFIG::SHOPID => $this->id,
                    T\CONFIG::MODULE => "{$module->type}:{$module->id}",
                ], [
                    T\CONFIG::SHOPID => $this->id,
                    T\CONFIG::MODULE => $module->id,
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
        return php\traverse(Extension::all($this), static function (Extension $extension) {
            return php\mapGroup($extension->type)->andKey($extension->id)->andValue($extension);
        });
    }

    /**
     * @see $modules
     * @return php\Map
     */
    protected function resolveModules(): php\Map
    {
        return php\map($this->extensions[Extension::MODULE] ?? []);
    }

    /**
     * @see $themes
     * @return php\Map
     */
    protected function resolveThemes(): php\Map
    {
        return php\map($this->extensions[Extension::THEME] ?? []);
    }

    /**
     * @see $config
     * @return php\Map
     */
    protected function resolveConfig(): php\Map
    {
        return $this->extensions[Extension::SHOP][Extension::SHOP]->config ?? php\map([]);
    }

    /**
     * @see $id
     * @return mixed
     */
    protected function resolveId()
    {
        return $this->query(T::SHOPS, function($id) {
            return $id;
        })->orderBy(T\SHOPS::ID)->limit(1)[0] ?? static::DEFAULT_ID;
    }

    protected function modulesConfig(): Generator
    {
        yield 'aDisabledModules' => php\map($this->modules, function(Extension $module) {
            return $module->status === $module::STATUS_INACTIVE ? $module->id : null;
        })->sort()->values;

        foreach (Extension::CONFIG_KEYS as $key => $property) {
            yield $key => php\map($this->modules, function(Extension $module) use($property) {
                return $module->status === $module::STATUS_ACTIVE && $module->$property ? $module->$property : null;
            })->sort(php\Map\Sort::KEYS)->traverse;
        }
    }
}
