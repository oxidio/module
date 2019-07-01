<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Meta;

use Generator;
use Webmozart\Glob\Glob;
use fn;

/**
 * @property-read ReflectionNamespace  $namespace
 * @property-read ReflectionConstant   $const
 * @property-read Template[]           $includes
 * @property-read ReflectionConstant[] $blocks
 * @property-read string               $path
 */
class Template
{
    use ReflectionTrait;

    protected const DEFAULT = ['namespace' => null, 'path' => null, 'name' => null];

    /**
     * @see $blocks
     * @return array
     */
    protected function resolveBlocks(): array
    {
        $diff = explode('_', $this->const->shortName);

        return fn\traverse($this->tags('block', 'name'), function(string $block, string $value) use($diff) {
            $block = implode('_', array_diff(explode('_', $block), $diff));
            $block = $block  ?  "BLOCK_{$block}" : 'BLOCK';

            $this->const->add('docBlock', 'blocks:', "@see \\{$this->const}\\{$block}");

            $const = ReflectionConstant::get("{$this->const}\\{$block}", [
                'value' => "'{$value}'",
            ]);
            $const->namespace->add('docBlock', "@see \\{$this->const}");
        });
    }

    /**
     * @see $includes
     * @return array
     */
    protected function resolveIncludes(): array
    {
        return fn\traverse($this->tags('include', 'file'), function(string $name) {
            return static::get($name);
        });
    }

    /**
     * @see $const
     * @return ReflectionConstant
     */
    protected function resolveConst(): ReflectionConstant
    {
        $includes = fn\traverse($this->includes, function(Template $template) {
            return "@see $template";
        });

        return ReflectionConstant::get($this->namespace . self::unify($this->name), [
            'value'    => var_export($this->name, true),
            'docBlock' => $includes ? ['includes:'] + $includes : [],
        ]);
    }

    /**
     * @see $namespace
     * @return ReflectionNamespace
     */
    protected function resolveNamespace(): ReflectionNamespace
    {
        return ReflectionNamespace::get((string)($this->properties['namespace'] ?? null));
    }

    /**
     * @param string $glob
     *
     * @param array $properties
     * @return Generator|self[]
     */
    public static function find(string $glob, array $properties = []): Generator
    {
        $basePath = Glob::getBasePath($glob);
        $offset   = strlen($basePath) + 1;
        foreach (Glob::glob($glob) as $path) {
            $name = substr($path, $offset);
            yield $name => static::get($name, ['path' => $path] + $properties);
        }
    }

    private static function unify(string $string = null): string
    {
        return str_replace(['.TPL', '/', '_'], ['', '_', '_'], strtoupper($string));
    }

    private function tags(string $tag, string $param): array
    {
        $pattern = '/' . sprintf('\[{\s*%s\s+%s\s*=\s*"([^"]+)"\s*}\]', $tag, $param) . '/';
        $content = file_get_contents($this->path);
        $matches = [];
        preg_match_all($pattern, $content, $matches);
        return fn\traverse($matches[1] ?? [], function(string $match) {
            return fn\mapKey($match)->andValue(self::unify($match));
        });
    }
}
