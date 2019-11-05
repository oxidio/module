<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core;

use Php;
use Oxidio;
use Generator;
use Oxidio\Enum\Tables as T;

/**
 * @property-read string              $configKey
 * @property-read string              $id
 * @property Php\Map                  $config
 * @property-read Php\Map|Extension[] $modules
 * @property-read Php\Map|Extension[] $themes
 * @property-read Database            $db
 */
class Shop implements DataModificationInterface
{
    /**
     * @see \Php\PropertiesTrait::propResolver
     * @uses resolveId, resolveConfig, resolveModules, resolveThemes, resolveExtensions
     */

    use Php\PropertiesTrait\ReadOnly;

    /**
     * @var string
     */
    protected const DEFAULT_CONFIG_KEY = 'fq45QS09_fqyx09239QQ';

    /**
     * @var int|string
     */
    protected const DEFAULT_ID = 1;

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
            $arg = Php\isCallable($arg) ? $arg($this) : $arg;
            foreach (is_iterable($arg) ? $arg : [] as $view => $value) {
                $value = Php\isCallable($value) ? $value($this) : $value;
                if (is_string($view)) {
                    $modify = $this->modify($view);
                    $value === null ? $modify->delete() : $modify->replace($value, T\SHOPS::ID);
                } else if (is_iterable($value)) {
                    Php\traverse($value);
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
        return Php\traverse(Extension::all($this), static function (Extension $extension) {
            return Php\mapGroup($extension->type)->andKey($extension->id)->andValue($extension);
        });
    }

    /**
     * @see $modules
     * @return Php\Map
     */
    protected function resolveModules(): Php\Map
    {
        return Php\map($this->extensions[Extension::MODULE] ?? []);
    }

    /**
     * @see $themes
     * @return Php\Map
     */
    protected function resolveThemes(): Php\Map
    {
        return Php\map($this->extensions[Extension::THEME] ?? []);
    }

    /**
     * @see $config
     * @return Php\Map
     */
    protected function resolveConfig(): Php\Map
    {
        return $this->extensions[Extension::SHOP][Extension::SHOP]->config ?? Php\map([]);
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
        yield 'aDisabledModules' => Php\map($this->modules, function(Extension $module) {
            return $module->status === $module::STATUS_INACTIVE ? $module->id : null;
        })->sort()->values;

        foreach (Extension::CONFIG_KEYS as $key => $property) {
            yield $key => Php\map($this->modules, function(Extension $module) use($property) {
                return $module->status === $module::STATUS_ACTIVE && $module->$property ? $module->$property : null;
            })->sort(Php\Map\Sort::KEYS)->traverse;
        }
    }
}
