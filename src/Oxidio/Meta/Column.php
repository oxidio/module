<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Meta;

/**
 * @property-read Table              $table
 * @property-read ReflectionConstant $const
 * @property-read string             $comment
 * @property-read string             $type
 * @property-read bool               $isPrimaryKey
 * @property-read bool               $isAutoIncrement
 * @property-read mixed              $default
 * @property-read int                $length
 */
class Column
{
    use ReflectionTrait;

    protected const DEFAULT = [
        'const'           => null,
        'table'           => null,
        'comment'         => null,
        'type'            => null,
        'isPrimaryKey'    => false,
        'isAutoIncrement' => false,
        'length'          => null,
        'default'         => null,
        'name'            => null,
    ];


    /**
     * @inheritDoc
     */
    protected function init(): void
    {
        $this->const;
    }

    /**
     * @see $const
     * @return ReflectionConstant
     */
    protected function resolveConst(): ReflectionConstant
    {
        $type = $this->type;
        $this->length > 0 && $type .= "({$this->length})";
        $this->default !== null && $type .= " = {$this->default}";

        $tableConst = $this->table->const;

        $const = ReflectionConstant::get([$tableConst, strtoupper($this->name)], [
            'value'    => "'{$this->name}'",
            'docBlock' => [$this->comment, '', $type]]
        );
        $const->namespace->add('docBlock',"@see \\{$tableConst}");
        if ($field = $this->table->class->fields[$this->name] ?? null) {
            $field->setValue($tableConst->namespace->shortName . $tableConst->shortName . '\\' . $const->shortName);
        }

        return $const;
    }
}
