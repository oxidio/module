<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Module;

use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Theme;
use fn;
use DI;

return [
    TITLE    => 'bar module (oxidio)',
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
            Theme\LAYOUT_BASE\BLOCK_HEAD_TITLE => overwrite(function(FrontendController $ctrl, Config $config) {
                return get_class($ctrl);
            }),
        ],
        Theme\LAYOUT_FOOTER => [
            Theme\LAYOUT_FOOTER\BLOCK_MAIN => append(function() {
            }),
        ],
    ],

    CLI   => DI\decorate(function(fn\Cli $cli) {
        $cli->command('bar', function(fn\Cli\IO $io) {
            $io->success('bar');
        });
        return $cli;
    })
];
