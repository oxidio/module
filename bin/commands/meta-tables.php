<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio;

use Closure;
use php;
use Symfony\Component\Filesystem\Filesystem;
use Oxidio\Enum;

return new class
{
    private $class;
    private $dir;
    private $ns;
    protected $fs;

    private function dump(string $dir, $ns, $class, Closure $closure): void
    {
        ($this->fs ?: $this->fs = new Filesystem())->dumpFile(
            "$dir/$class.php",
            new php\Map(function () use ($ns, $class, $closure) {
                yield <<<EOL
<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace {$ns};

interface {$class}
{
EOL;
                yield from new php\Map($closure);
                yield '}';
            })
        );
    }

    /**
     * Analyze and generate table constants (tables, columns)
     *
     * @param php\Cli\IO $io
     * @param Core\Shop  $shop
     * @param string     $class class name [Oxidio\Enum\Tables]
     * @param string     $dir
     */
    public function __invoke(
        php\Cli\IO $io,
        Core\Shop $shop,
        string $class = Enum\Tables::class,
        string $dir = null
    ) {
        $ns = explode('\\', $class);
        $this->class = array_pop($ns);
        $this->dir = $dir ?: implode('-', $ns + ['time' => time()]);
        $this->ns = implode('\\', $ns);
        $file = "{$this->dir}/{$this->class}.php";
        $io->isVerbose() && $io->note(var_export([
            'dir' => $this->dir,
            'class' => $this->class,
            'namespace' => $this->ns,
            'file' => $file,
        ], true));

        $this->dump($this->dir, $this->ns, $this->class, function () use ($shop) {
            foreach ((new Meta\Provider(['db' => $shop->db]))->tables as $table) {
                $class = strtoupper(Meta\Provider::beautify($table));
                yield '    /**';
                yield "     * {$table->comment} [{$table->engine}]";
                yield '     *';
                yield "     * @see \\{$table->class->parent}";
                yield '     */';
                yield "     public const {$class} = '{$table}';";
                yield;

                $ns = "{$this->ns}\\{$this->class}";
                $this->dump("{$this->dir}/{$this->class}", $ns, ucwords(strtolower($class)), function () use ($table) {
                    foreach ($table->columns as $column) {
                        $const = strtoupper(Meta\Provider::beautify($column));
                        $type = $column->type;
                        $column->length > 0 && $type .= "({$column->length})";
                        $column->default !== null && $type .= " = {$column->default}";

                        yield '    /**';
                        yield "     * {$column->comment}";
                        yield '     *';
                        yield "     * $type";
                        yield '     */';
                        yield "     public const {$const} = '{$column}';";
                        yield;
                    }
                });
            }
        });
    }
};
