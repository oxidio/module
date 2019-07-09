<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Cli;

use fn\{Cli\IO};
use fn;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\BaseModel;
use Oxidio;
use Oxidio\Meta\{EditionClass, ReflectionConstant, ReflectionNamespace, Table};

/**
 * Analyze and generate model namespace constants (tables, columns, fields)
 *
 * @param IO     $io
 * @param bool   $filterTable    Filter classes with db tables (not abstract models)
 * @param bool   $filterTemplate Filter classes with templates (not abstract controllers)
 * @param bool   $tablesConst    Create TABLES constant
 * @param string $filter         Filter classes by pattern
 * @param string $dbNs           Namespace for database constants [OxidEsales\Eshop\Core\Database]
 * @param string $fieldNs        Namespace for field constants [OxidEsales\Eshop\Core\Field]
 */
return static function (
    IO $io,
    bool $filterTable = false,
    bool $filterTemplate = false,
    bool $tablesConst = false,
    string $filter = null,
    string $dbNs = 'OxidEsales\\Eshop\\Core\\Database',
    string $fieldNs = Field::class
) {

    $onVerbose = static function (IO $io, EditionClass $class): void {
        $io->title($class->name);
        $io->table(['property', 'value'], [
            ['package', $class->package],
            ['table', $table = $class->table],
            ['template', $class->template],
        ]);

        if ($table && $io->isVeryVerbose()) {
            $io->listing($class->fields);
            $table->comment;
        }
    };

    $tableNs = $dbNs . '\\TABLE';

    $shop = new Oxidio\Meta\Shop(['tableNs' => $tableNs, 'fieldNs' => $fieldNs]);

    foreach ($shop->classes as $name => $class) {
        if ($filter && stripos($class->package, $filter) === false) {
            continue;
        }
        if ($filterTable && !$class->table) {
            continue;
        }
        if ($filterTemplate && !$class->template) {
            continue;
        }
        $io->isVerbose() && $onVerbose($io, $class);
    }

    foreach ($shop->tables as $table) {
        if ($table->class->name === BaseModel::class) {
            $io->isVerbose() && $io->writeln("table $table has no class");
        }
    }

    $tablesConst && ReflectionConstant::get($dbNs . '\\TABLES', [
        'value' => fn\map(static function () {
            yield '[';
            foreach (Table::cached() as $table) {
                $ns = $table->const->namespace->shortName . $table->const->shortName;
                yield "        $ns => [";
                foreach ($table->columns as $column) {
                    $field = strpos($column, 'ox') === 0  ? substr($column, 2) : $column;
                    yield "            '$field' => $ns\\{$column->const->shortName},";
                }
                yield '        ],';
            }
            yield '    ]';
        })->string,
    ])->namespace->add('use', $tableNs);


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

    foreach (ReflectionNamespace::all() as $namespace) {
        foreach ($namespace->toPhp() as $line) {
            $io->writeln($line);
        }
        $io->writeln('');
    }
};
