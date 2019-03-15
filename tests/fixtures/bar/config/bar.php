<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Module;

use OxidEsales\Eshop\Application\Controller\StartController;
use OxidEsales\Eshop\Core\Theme;

return [
    ID       => 'oxidio/module-bar',
    TITLE    => 'bar module (oxidio)',
    URL      => 'https://github.com/oxidio',
    AUTHOR   => 'oxidio',
    SETTINGS => [
        'foo' => [
            'string' => [SETTINGS\VALUE => 'string'],
            'true'   => [SETTINGS\VALUE => true],
            'false'  => [SETTINGS\VALUE => false],
            'aarr'   => [SETTINGS\VALUE => ['a' => 'A', 'b' => 'B']],
        ],
        'bar' => [
            'selected' => [SETTINGS\VALUE => ['c' => 'C', 'd' => 'D', 'e' => 'E'], SETTINGS\SELECTED => 'd']
        ]
    ],

    BLOCKS   => [
        Theme\LAYOUT_BASE   => [
            Theme\LAYOUT_BASE\BLOCK_HEAD_META_ROBOTS  => prepend(function() {

            }),
            Theme\LAYOUT_BASE\BLOCK_HEAD_TITLE => overwrite(function($oView) {
                return get_class($oView);
            }),
        ],
        Theme\LAYOUT_FOOTER => [
            Theme\LAYOUT_FOOTER\BLOCK_MAIN => append(function() {
            }),
        ],
    ],
];
