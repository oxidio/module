<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Module;

use DI;
use fn;

return [
    ID       => ID,
    TITLE    => TITLE,
    URL      => URL,
    AUTHOR   => AUTHOR,
    SETTINGS => [
        'group' => [
            'string'   => [Settings\VALUE => 'string'],
            'true'     => [Settings\VALUE => true],
            'false'    => [Settings\VALUE => false],
            'select'   => [Settings\VALUE => ['a', 'b', 'c']],
            'selected' => [Settings\VALUE => ['a', 'b', 'c'], Settings\SELECTED => 'a'],
        ]
    ],
    BLOCKS   => ['t1.tpl' => 'b1', 't2.tpl' => ['b2', 'b3' => 'b3.tpl']],

    'cli'    => DI\decorate(function(fn\Cli $cli) {
        $cli->command('c1', function(fn\Cli\IO $io) {
            $io->success('c1');
        });
        return $cli;
    })
];
