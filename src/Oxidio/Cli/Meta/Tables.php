<?php declare(strict_types=1);
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Cli\Meta;

use Closure;
use Generator;
use OxidEsales\Eshop\Core\Model\BaseModel;
use Php;
use Symfony\Component\Filesystem\Filesystem;
use Oxidio;

class Tables
{
    private $class;
    private $tableClass;
    private $dir;
    private $fs;

    /**
     * Analyze and generate table constants (tables, columns)
     *
     * @param Php\Cli\IO       $io
     * @param Oxidio\Core\Shop $shop
     * @param string           $class class name [Oxidio\Enum\Tables]
     * @param string           $dir
     */
    public function __invoke(
        Php\Cli\IO $io,
        Oxidio\Core\Shop $shop,
        string $class = Oxidio\Enum\Tables::class,
        string $dir = null
    ) {
        $this->dir = $dir ?: str_replace('\\', '-', static::class . '-' . time());
        $this->class = $class;
        $this->file(function () use ($shop) {
            foreach ((new Oxidio\Meta\Provider(['db' => $shop->db]))->tables as $table) {
                yield from $this->table($table);
            }
        }, $this->class);
    }

    private function file(Closure $closure, string $class, string $docBlock = ''): void
    {
        $ns = explode('\\', $class);
        $class = array_pop($ns);
        $ns = implode('\\', $ns);
        $file = str_replace('\\', '/', "{$this->dir}/$ns/$class.php");
        $this->fs ?: $this->fs = new Filesystem();
        $this->fs->dumpFile($file, <<<EOL
<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace {$ns};

/**
 * {$docBlock}
 */
interface {$class}
{

EOL
        );
        $this->fs->appendToFile($file, new Php\Map($closure));
        $this->fs->appendToFile($file, '}');
    }

    private function table(Oxidio\Meta\Table $table): Generator
    {
        $const = $this::constName($table->name, $this->class, ['ox' => '', 'object2' => 'o2']);
        $this->tableClass = $this->class . '\\' . $const;

        $docBlock = "{$table->comment} [{$table->engine}]";
        yield '    /**';
        yield "     * {$docBlock}";
        if ($table->class->name !== BaseModel::class) {
            yield '     *';
            yield "     * @see \\{$table->class}";
        }
        yield '     */';
        yield "    public const {$const} = '{$table}';";
        yield;
        $this->file(function () use ($table) {
            foreach ($table->columns as $column) {
                yield from $this->column($column);
            }
        }, $this->tableClass, $docBlock);
    }

    private function column(Oxidio\Meta\Column $column): Generator
    {
        $const = $this::constName($column->name, $this->tableClass);
        $type = $column->type;
        $column->length > 0 && $type .= "({$column->length})";
        $column->default !== null && $type .= " = {$column->default}";

        yield '    /**';
        yield "     * {$column->comment}";
        yield '     *';
        yield "     * $type";
        yield '     */';
        yield "    public const {$const} = '{$column}';";
        yield;
    }

    private static function constName(string $value, string $class, $replace = 'ox'): string
    {
        return Oxidio\Oxidio::constName($value, $class) ??
            strtoupper(Php\Lang::sanitize(Oxidio\Oxidio::after($value, $replace)));
    }
}
