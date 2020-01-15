<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Module;

use Generator;
use IteratorAggregate;
use JsonSerializable;
use Php;

/**
 */
class Blocks implements IteratorAggregate, JsonSerializable
{
    public const DIRECTORY = 'views/blocks/';
    public const EXTENSION = '.tpl';

    /**
     * @var Block[][]
     */
    private $templates;

    /**
     * @var null
     */
    private $serialized;

    /**
     * @param Block[][] $templates
     */
    public function __construct(iterable $templates)
    {
        $this->templates = $templates;
    }

    /**
     * @inheritdoc
     */
    public function getIterator(): Generator
    {
        foreach ($this->templates as $template => $blocks) {
            foreach ($blocks as $name => $block) {
                $file = substr($template, 0, -strlen(self::EXTENSION)) . "-{$name}";

                $block->template = $template;
                $block->name     = $name;
                $block->file     = self::DIRECTORY . $file . self::EXTENSION;

                yield $block->file => $block;
            }
        }
    }

    /**
     * @param string $file
     *
     * @return Block|null
     */
    public function get(string $file): ?Block
    {
        return $this->jsonSerialize()[$file] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return $this->serialized ?: $this->serialized = Php::traverse($this);
    }
}
