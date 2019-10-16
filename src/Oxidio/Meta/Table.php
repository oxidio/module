<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Meta;

use Doctrine\DBAL\Schema\Table as SchemaTable;
use php;
use Oxidio;

/**
 * @property-read EditionClass       $class
 * @property-read ReflectionConstant $const
 * @property-read Column[]           $columns
 * @property-read string             $comment
 * @property-read string             $engine
 */
class Table
{
    use ReflectionTrait;
    protected const DEFAULT = ['const' => null, 'class' => null, 'columns' => null];

    /**
     * @inheritDoc
     */
    protected function init(): void
    {
        $this->columns;
    }

    private function detail($detail): ?string
    {
        static $details;
        $details || $details = php\traverse($this->provider->db->tables, function (SchemaTable $table) {
            return php\mapKey($table->getName())->andValue($table->getOptions());
        });
        return $details[$this->name][$detail] ?? null;
    }

    /**
     * @see $const
     * @return ReflectionConstant
     */
    public function resolveConst(): ReflectionConstant
    {
        $table = strtoupper($this->name);
        return $this->provider->const([$this->class->tableNs, $table], [
            'value' => "'{$this->name}'",
            'docBlock' => [
                "{$this->comment} [{$this->engine}]",
                '',
                "@see {$table}\\*"
            ]
        ]);
    }

    /**
     * @see $comment
     * @return string|null
     */
    protected function resolveComment(): ?string
    {
        return $this->detail('comment');
    }

    /**
     * @see $engine
     * @return string|null
     */
    protected function resolveEngine(): ?string
    {
        return $this->detail('engine');
    }

    /**
     * @see $columns
     * @return array
     */
    protected function resolveColumns(): array
    {
        $columns = [];
        $nls = [];
        foreach ($this->provider->db->metaColumns($this->name) as $column) {
            $name = strtolower($column->name);
            if (is_numeric(substr($name, ($last = strrpos($name, '_')) + 1))) {
                $nls[substr($name, 0, $last)] = true;
                continue;
            }
            $columns[$name] = [
                'table' => $this,
                'name' => $name,
                'type' => $column->type,
                'comment' => $column->comment,
                'isPrimaryKey' => $column->primary_key,
                'isAutoIncrement' => $column->auto_increment,
                'length' => $column->max_length,
                'default' => $column->has_default ?? null ? $column->default_value : null,
            ];
        }

        return php\traverse($columns, function (array $column, $name) use ($nls) {
            $column['type'] .= (($nls[$name] ?? false) ? '-i18n' : '');
            return new Column($this->provider, ['name' => $name] + $column);
        });
    }
}
