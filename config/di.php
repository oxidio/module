<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Module;

use fn;
use DI;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;

return [
    ID       => null,
    TITLE    => DI\get(ID),
    URL      => null,
    AUTHOR   => null,
    SETTINGS => [],
    BLOCKS   => [],

    'cli'    => function(ContainerInterface $container): fn\Cli {
        return fn\cli($container, [
            'cli.name'             => $container->get(TITLE),
            'cli.commands.default' => DI\value(function(Command $command) {
                return $command->setHidden(true);
            }),
        ]);
    },

    'metadata' => function(Module $module): array {
        return json_decode(json_encode($module), true);
    }
];
