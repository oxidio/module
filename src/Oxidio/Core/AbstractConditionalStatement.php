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
 * @property-read array $whereTerms
 */
abstract class AbstractConditionalStatement
{
    use fn\PropertiesTrait\ReadOnly;

    /**
     * @param callable $db
     *
     * @return $this
     */
    public function withDb(callable $db): self
    {
        $this->properties['db'] = $db;
        return $this;
    }

    protected function getColumnName($candidate): string
    {
        return $candidate;
    }

    /**
     * @param array ...$terms
     *
     * @return static
     */
    public function where(...$terms)
    {
        $this->properties['whereTerms'] = array_filter($terms);
        return $this;
    }

    /**
     * @param array $terms
     * @param string $prefix
     *
     * @return string
     */
    public function buildWhere(array $terms, string $prefix = "\nWHERE "): string
    {
        $where = implode(' OR ', fn\traverse($terms, function ($term) {
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
        return $where ? $prefix . $where : $where;
    }

    protected function resolveView(): void
    {
        fn\fail(__METHOD__);
    }

    public function resolveDb(): void
    {
        fn\fail(__METHOD__);
    }

    public function resolveWhereTerms(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        return $this->buildWhere($this->whereTerms);
    }
}
