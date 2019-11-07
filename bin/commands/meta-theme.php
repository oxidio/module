<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio;

use Php\{Cli\IO};
use Php;
use OxidEsales\Eshop\Core\Theme;

/**
 * Analyze and generate theme namespace constants (templates, blocks, includes)
 *
 * @param IO       $io
 * @param bool     $filterBlock Filter templates with blocks
 * @param bool     $filterInclude Filter templates with includes
 * @param string   $basePath [%OX_BASE_PATH% . Application/views/flow/tpl/]
 * @param string   $themeNs Namespace for theme constants [OxidEsales\Eshop\Core\Theme]
 * @param string   $glob [** / *.tpl]
 */
return static function (
    IO $io,
    bool $filterBlock,
    bool $filterInclude,
    string $basePath = OX_BASE_PATH . 'Application/views/flow/tpl/',
    string $themeNs = Theme::class,
    string $glob = '**/*.tpl'
) {

    $provider = new Meta\Provider(['themeNs' => $themeNs]);

    foreach ($provider->templates($basePath . $glob) as $template) {
        if ($filterBlock && !$template->blocks) {
            continue;
        }
        if ($filterInclude && !$template->includes) {
            continue;
        }
        $io->isVerbose() && (static function () use ($io, $template): void {
            $keyValue = function(string $value, string $key) {
                return "$key ($value)";
            };
            $io->title("{$template->const->shortName} ({$template->name})");
            $io->isVeryVerbose() && $io->listing(Php::traverse($template->blocks, $keyValue));
            $io->isVeryVerbose() && $io->listing(Php::traverse($template->includes, $keyValue));
        })();

        $template->blocks;
    }

    $io->writeln([
        '<?php',
        '/**',
        ' * Copyright (C) oxidio. See LICENSE file for license details',
        ' *',
        " * autogenerated by {$io->getInput()}",
        ' */',
        '/** @noinspection SpellCheckingInspection */',
        '',
    ]);

    foreach ($provider->namespaces as $namespace) {
        foreach ($namespace->toPhp() as $line) {
            $io->writeln($line);
        }
        $io->writeln('');
    }
};
