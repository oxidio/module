<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core;

use fn;
use Oxidio;

/**
 * @property-read callable $db
 * @property-read string $view
 */
abstract class AbstractConditionalStatement
{
    use fn\PropertiesReadOnlyTrait;

    protected $whereTerm;
    protected $data = [];

    /**
     * @inheritDoc
     */
    protected function propertyMethodInvoke(string $name)
    {
        if (!fn\hasKey($name, $this->data)) {
            $this->data[$name] = $this->{$this->propertyMethod($name)->name}();
        }
        return $this->data[$name];
    }

    /**
     * @param callable $db
     *
     * @return $this
     */
    public function withDb(callable $db): self
    {
        $this->data['db'] = $db;
        return $this;
    }

    protected function resolveDb(): Oxidio\Core\Database
    {
        return Oxidio\db();
    }

    protected function getColumnName($candidate): string
    {
        return $candidate;
    }

    /**
     * @param array ...$terms
     *
     * @return $this
     */
    public function where(...$terms): self
    {
        $this->whereTerm = $this->buildWhere($terms);
        return $this;
    }

    /**
     * @param array $terms
     *
     * @return string
     */
    public function buildWhere(array $terms): string
    {
        return implode(' OR ', fn\traverse($terms, function ($term) {
            if ($term = is_iterable($term) ? implode(' AND ', fn\traverse($term, function($candidate, $column) {
                $value = $candidate;
                $operator = null;
                if (is_iterable($candidate)) {
                    $column = $candidate['column'] ?? $column;
                    $operator = $candidate['op'] ?? $candidate[0] ?? null;
                    $value = $candidate['value'] ?? $candidate[1] ?? null;
                }

                if ($value === null) {
                    $value = 'NULL';
                    $operator = $operator ?: 'IS';
                } else {
                    $value = "'{$value}'";
                    $operator = $operator ?: '=';
                }
                return "{$this->getColumnName($column)} {$operator} {$value}";
            })) : $term) {
                return "($term)";
            }
            return null;
        }));
    }

    protected function resolveView(): void
    {
        fn\fail(__METHOD__);
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        return $this->whereTerm ? "\nWHERE {$this->whereTerm}" : '';
    }
}
