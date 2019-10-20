<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Cli\Meta;

use Closure;
use Generator;
use php;
use ReflectionClass;
use ReflectionException;
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
     * @param php\Cli\IO $io
     * @param Oxidio\Core\Shop  $shop
     * @param string     $class class name [Oxidio\Enum\Tables]
     * @param string     $dir
     */
    public function __invoke(
        php\Cli\IO $io,
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

    private function file(Closure $closure, string $class): void
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

interface {$class}
{

EOL
);
        $this->fs->appendToFile($file, new php\Map($closure));
        $this->fs->appendToFile($file, '}');
    }

    private function table(Oxidio\Meta\Table $table): Generator
    {
        $const = $this::constName($table->name, $this->class, ['ox' => '', 'object2' => 'o2']);
        $this->tableClass = $this->class . '\\' . $const;

        yield '    /**';
        yield "     * {$table->comment} [{$table->engine}]";
        yield '     *';
        yield "     * @see \\{$table->class}";
        yield '     */';
        yield "    public const {$const} = '{$table}';";
        yield;
        $this->file(function () use ($table) {
            foreach ($table->columns as $column) {
                yield from $this->column($column);
            }
        }, $this->tableClass);
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

    private static function constName(string $value, string $class, $replacePrefix = 'ox'): string
    {
        static $cache = [];
        if (!isset($cache[$class])) {
            try {
                $cache[$class] = array_flip((new ReflectionClass($class))->getConstants());
            } catch (ReflectionException $e) {
                $cache[$class] = [];
            }
        }
        return strtoupper(Oxidio\Oxidio::sanitize(Oxidio\Oxidio::after($value, $replacePrefix)));
    }
}
