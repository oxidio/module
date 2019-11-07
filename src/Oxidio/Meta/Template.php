<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Meta;

use Php;

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

    protected const DEFAULT = ['path' => null];

    /**
     * @see $blocks
     * @return array
     */
    protected function resolveBlocks(): array
    {
        $diff = explode('_', $this->const->shortName);

        return Php::traverse($this->tags('block', 'name'), function (string $block, string $value) use ($diff) {
            $block = implode('_', array_diff(explode('_', $block), $diff));
            $block = $block ? "BLOCK_{$block}" : 'BLOCK';

            $this->const->add('docBlock', 'blocks:', "@see \\{$this->const}\\{$block}");

            $const = $this->provider->const([$this->const, $block], [
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
        return Php::traverse($this->tags('include', 'file'), function (string $name) {
            return $this->provider->template($name);
        });
    }

    /**
     * @see $const
     * @return ReflectionConstant
     */
    protected function resolveConst(): ReflectionConstant
    {
        $includes = Php::traverse($this->includes, function (Template $template) {
            return "@see $template";
        });

        return $this->provider->const($this->namespace . self::unify($this->name), [
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
        return $this->provider->ns((string)($this->properties['namespace'] ?? null));
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
        return Php::traverse($matches[1] ?? [], function (string $match) {
            return Php::mapKey($match)->andValue(self::unify($match));
        });
    }
}
