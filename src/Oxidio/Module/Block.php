<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Module;

use JsonSerializable;
use fn;
use OxidEsales\Eshop\Application\Controller\FrontendController;

/**
 */
class Block implements JsonSerializable
{
    public const APPEND     = true;
    public const PREPEND    = false;
    public const OVERWRITE  = null;

    private const PARENT  = '[{$smarty.block.parent}]';

    /**
     * @var string
     */
    public $template;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $file;

    /**
     * @var callable
     */
    public $callback;

    /**
     * @var mixed
     */
    protected $action;

    /**
     * @param callable $callback
     * @param bool|null $action
     */
    public function __construct($callback, $action = self::APPEND)
    {
        $this->callback = $callback;
        $this->action   = $action;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return ['template' => $this->template, 'block' => $this->name, 'file' => $this->file];
    }

    /**
     * @see Module::renderBlock
     * @see \Smarty::get_template_vars
     * @see \smarty_prefilter_oxblock
     */
    public function __toString()
    {
        return implode(PHP_EOL, [
            $this->action === self::APPEND ? self::PARENT : null,
            '[{php}]',
            '    echo ' . Module::class . "::instance('%s')->renderBlock(",
            "        '{$this->file}',",
            '        $this->get_template_vars()',
            '    )',
            '[{/php}]',
            $this->action === self::PREPEND ? self::PARENT : null,
        ]);
    }
}
