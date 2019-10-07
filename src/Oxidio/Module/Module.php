<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Module;

use OxidEsales\EshopCommunity\Internal\Application\BootstrapContainer\BootstrapContainerFactory;
use OxidEsales\EshopCommunity\Internal\Application\Utility\BasicContextInterface;
use Oxidio;
use php;
use Generator;
use JsonSerializable;
use OxidEsales\Eshop\Core\Module\Module as OxidModule;
use Oxidio\DI\RegistryResolver;
use Oxidio\DI\SmartyTemplateVars;
use Symfony\Component\Filesystem\Filesystem;
use Invoker\ParameterResolver;

/**
 * @property-read string $id
 * @property-read php\Package $package
 * @property-read php\Cli $cli
 * @property-read php\DI\Container $container
 * @property-read BasicContextInterface $context
 * @property-read string[] $languages
 * @property-read php\DI\Invoker $invoker
 */
class Module implements JsonSerializable
{
    /**
     * @see \php\PropertiesTrait::propResolver
     * @uses resolveContext, resolveLanguages, resolvePackage, resolveContainer, resolveCli, resolveInvoker
     */

    use php\PropertiesTrait\ReadOnly;

    /**
     * @var static[]
     */
    private static $cache = [];

    /**
     * @see $context
     * @return BasicContextInterface
     */
    protected function resolveContext(): BasicContextInterface
    {
        static $cache;
        return $cache ?: $cache = BootstrapContainerFactory::getBootstrapContainer()->get(BasicContextInterface::class);
    }

    protected function resolveLanguages(): array
    {
        static $cache;
        if ($cache === null) {
            if (!function_exists('getLanguages')) {
                require_once $this->context->getCommunityEditionSourcePath() . '/Setup/functions.php';
            }
            $cache = getLanguages() ?: ['en', 'de'];
        }
        return $cache;
    }

    /**
     * @see $package
     * @return php\Package
     */
    protected function resolvePackage(): php\Package
    {
        return php\package($this->id);
    }

    /**
     * @see $container
     * @return php\DI\Container
     */
    protected function resolveContainer(): php\DI\Container
    {
        $package = $this->package;
        ($di = $package->extra['di'] ?? []) && $di = $package->file($di);
        return php\di(
            [ID => $this->id, self::class => $this,],
            $di,
            php\Composer\DIClassLoader::instance()->getContainer()
        );
    }

    /**
     * @see $cli
     * @return php\Cli
     */
    protected function resolveCli(): php\Cli
    {
        $cli = Oxidio\cli($this->package, $this->container);
        $this->container->set(get_class($cli), $cli);
        $this->container->set(CLI, $this->get(CLI, $cli));
        return $cli;
    }

    /**
     * @param string $id
     */
    public function __construct(string $id)
    {
        $this->properties['id'] = $id;
    }

    private function getBlocks(): Blocks
    {
        return new Blocks($this->get(BLOCKS, []));
    }

    /**
     * @param string $id
     *
     * @return static
     */
    public static function instance(string $id): self
    {
        return self::$cache[$id] ?? self::$cache[$id] = new static($id);
    }

    /**
     * @param string|iterable $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function get($name, $default = null)
    {
        if (is_iterable($name)) {
            return php\traverse($name, function ($default, $name) {
                if (is_numeric($name)) {
                    $name    = $default;
                    $default = php\mapNull();
                }
                return php\mapKey($name)->andValue(
                    $this->container->has($name) ? $this->container->get($name) : ($this->$name ?? $default)
                );
            });
        }
        return $this->container->has($name) ? $this->container->get($name) : ($this->$name ?? $default);
    }

    public function renderBlock(string $file): string
    {
        if ($block = $this->getBlocks()->get($file)) {
            return (string) $this->invoker->call($block->callback);
        }
        return '';
    }

    public function renderApp($menuKey): string
    {
        if ($menu = $this->getMenu(true)[$menuKey] ?? null) {
            return (string) $this->invoker->call($menu->callback);
        }
        return '';
    }

    /**
     * @see $invoker
     * @return php\DI\Invoker
     */
    protected function resolveInvoker(): php\DI\Invoker
    {
        return new php\DI\Invoker(
            new SmartyTemplateVars,
            new ParameterResolver\AssociativeArrayResolver,
            $this->container,
            new ParameterResolver\Container\ParameterNameContainerResolver($this->container),
            new RegistryResolver,
            new ParameterResolver\DefaultValueResolver
        );
    }

    /**
     * @param bool       $enable
     * @param OxidModule $module
     *
     * @return bool
     */
    public function activate(bool $enable, OxidModule $module): bool
    {
        $enable && $this->generateFiles($module->getModuleFullPath(), true);
        return true;
    }

    private function generateFiles(string $path, bool $force = false): void
    {
        $fs = new Filesystem();
        $modulesDir = $this->context->getModulesPath();
        if (!$force && strpos($path, $modulesDir) !== 0 && $fs->exists("{$path}/menu.xml")) {
            return;
        }

        $fs->dumpFile($modulesDir . APP_TPL, implode(PHP_EOL, [
            '[{ $oView->renderApp() }]',
        ]));

        $fs->remove("{$path}/views/");
        $fs->remove("{$path}/menu.xml");

        foreach ($this->languages as $lang) {
            $fs->dumpFile("{$path}/views/admin/$lang/module_options.php", implode(PHP_EOL, [
                '<?php',
                sprintf('// autogenerated by %s ', __METHOD__),
                sprintf('$aLang = %s;', var_export(php\traverse($this->getTranslations($lang)), true)),
            ]));
        }

        foreach ($this->getBlocks() as $file => $block) {
            $fs->dumpFile("{$path}/{$file}", sprintf($block, $this->id));
        }

        $fs->dumpFile("{$path}/menu.xml", php\map([
            '<?xml version="1.0" encoding="UTF-8"?>',
            sprintf('<!-- autogenerated by %s -->', __METHOD__),
            '<OX>',
            php\map(php\traverse($this->getMenu(), function(Menu $menu) {
                return (string) $menu;
            }))->string,
            '</OX>',
        ])->string);
    }

    private function params($key, MenuNode ...$nodes): void
    {
        foreach ($nodes as $node) {
            $node->params = php\traverse($node->params, function($value) use($key) {
                return php\isCallable($value) ? $value($this, $key) : $value;
            });
        }
    }

    /**
     * @param bool $flatten
     * @return Menu[]
     */
    public function getMenu(bool $flatten = false): array
    {
        return php\flatten(Menu::create($this->get(MENU, [])), function(
            Menu $menu,
            $key,
            php\Map\Path $it
        ) use($flatten) {
            $this->params($key, $menu, ...$menu->tabs, ...$menu->buttons);
            if ($flatten || !$it->getDepth()) {
                return $menu;
            }
            return null;
        });
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        $author = $this->package->authors[0] ?? [];
        return [
            ID          => $this->id,
            TITLE       => $this->get(TITLE, $this->id),
            DESCRIPTION => $this->get(DESCRIPTION, $this->package->description),
            URL         => $this->get(URL, $this->package->homepage),
            VERSION     => $this->get(VERSION, $this->package->version()),
            AUTHOR      => $this->get(AUTHOR, $author['name'] ?? ''),
            EMAIL       => $this->get(EMAIL, $author['email'] ?? ''),
            SETTINGS    => new Settings($this->get(SETTINGS, [])),
            BLOCKS      => $this->getBlocks(),
            EXTEND      => $this->get(EXTEND, []),
            'events'    => new Events,
            'templates' => [APP  => APP_TPL],
        ];
    }

    /**
     * @return array
     */
    public function getMetadata(): array
    {
        $this->generateFiles(dirname(debug_backtrace(false, 1)[0]['file'] ?? null));
        return json_decode(json_encode($this), true);
    }

    /**
     * @param string $lang
     *
     * @return Generator
     */
    private function getTranslations(string $lang): Generator
    {
        yield from (new Settings($this->get(SETTINGS, [])))->translate($lang);
        foreach ($this->getMenu(true) as $menu) {
            yield from $menu->translate($lang);
        }
    }
}
