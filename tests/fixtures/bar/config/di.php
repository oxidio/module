<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Module;

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
];
