<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Module;

use Generator;
use IteratorAggregate;

/**
 */
class Blocks implements IteratorAggregate
{
    /**
     * @var iterable
     */
    private $templates;

    /**
     * @var string
     */
    private $directory;

    /**
     * @var string
     */
    private $extension;

    /**
     * @param iterable $templates
     * @param string   $directory
     * @param string   $extension
     */
    public function __construct(iterable $templates, string $directory = 'views/blocks/', string $extension = '.tpl')
    {
        $this->templates = $templates;
        $this->directory = $directory;
        $this->extension = $extension;
    }

    /**
     * @inheritdoc
     */
    public function getIterator(): Generator
    {
        foreach ($this->templates as $template => $blocks) {
            foreach (is_iterable($blocks) ? $blocks : [$blocks] as $block => $file) {
                if (is_numeric($block)) {
                    $block = $file;
                    $file  = substr($template, 0, -strlen($this->extension)) . "-{$block}{$this->extension}";
                }
                yield ['template' => $template, 'block' => $block, 'file' => $this->directory . $file];
            }
        }
    }
}
