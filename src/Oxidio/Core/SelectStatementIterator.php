<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core;

use Iterator;
use OuterIterator;
use Oxidio;
use Php\Map\Tree;

class SelectStatementIterator implements OuterIterator
{
    private AbstractSelectStatement $query;
    private Database $db;
    private ?Iterator $it;
    private int $start;
    private int $chunkSize;
    private int $counter = 0;

    public function __construct(AbstractSelectStatement $query, Database $db, int $chunkSize = 500)
    {
        $this->query = $query;
        $this->db = $db;
        $this->start = $query->start;
        $this->chunkSize = min($chunkSize, $query->limit ?: $chunkSize);
    }

    public function getInnerIterator(): Iterator
    {
        if (!$this->it) {
            $this->counter = 0;
            $sql = $this->query->getSql($this->start, $this->chunkSize);
            $this->it = new Tree($this->db->query("($sql) v"));
        }
        return $this->it;
    }

    private function limitReached(): bool
    {
        return $this->query->limit && $this->start + $this->chunkSize >= $this->query->start + $this->query->limit;
    }

    public function valid(): bool
    {
        if ($this->counter >= $this->chunkSize && !$this->limitReached() && !$this->getInnerIterator()->valid()) {
            $this->it = null;
            $this->start += $this->chunkSize;
            $this->getInnerIterator()->rewind();
        }
        return $this->getInnerIterator()->valid();
    }

    public function next(): void
    {
        if ($this->getInnerIterator()->valid()) {
            $this->counter++;
        }
        $this->getInnerIterator()->next();
    }

    public function rewind(): void
    {
        $this->it = null;
        $this->start = $this->query->start;
        $this->getInnerIterator()->rewind();
    }

    public function current()
    {
        return $this->getInnerIterator()->current();
    }

    public function key()
    {
        return $this->getInnerIterator()->key();
    }
}
