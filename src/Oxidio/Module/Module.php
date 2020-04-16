<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Module;

use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use Oxidio;
use Php;
use Generator;
use JsonSerializable;
use OxidEsales\Eshop\Core\Module\Module as OxidModule;
use Oxidio\DI\RegistryResolver;
use Oxidio\DI\SmartyTemplateVars;
use Symfony\Component\Filesystem\Filesystem;
use Invoker\ParameterResolver;

/**
 * @property-read string           $id
 * @property-read Php\Package      $package
 * @property-read Php\Cli          $cli
 * @property-read Php\DI\Container $container
 * @property-read Php\DI\Invoker   $invoker
 * @property-read BasicContextInterface $context
 * @property-read string[] $languages
 */
class Module implements JsonSerializable
{
    /**
     * @see Php\PropertiesTrait::propResolver
     * @uses resolveContext, resolveLanguages, resolvePackage, resolveContainer, resolveCli, resolveInvoker
     */
    use Php\PropertiesTrait\ReadOnly;
    use Php\DI\AwareTrait;

    public const APP = 'oxidio-app';
    protected const APP_TPL = '/oxidio/views/admin/tpl/' . self::APP . '.tpl';
    public const MENU = 'menu';
    public const CLI = 'cli';
    public const EXTEND = 'extend';
    public const BLOCKS = 'blocks';
    public const SETTINGS = 'settings';
    public const EMAIL = 'email';
    public const AUTHOR = 'author';
    public const VERSION = 'version';
    public const URL = 'url';
    public const DESCRIPTION = 'description';
    public const TITLE = 'title';
    public const ID = 'id';

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
        return Oxidio::di(BasicContextInterface::class);
    }

    protected function resolveLanguages(): array
    {
        // @todo add service
        return  ['en', 'de'];
    }

    /**
     * @see $package
     * @return Php\Package
     */
    protected function resolvePackage(): Php\Package
    {
        return Php\Package::get($this->id);
    }

    /**
     * @see $container
     * @return Php\DI\Container
     */
    protected function resolveContainer(): Php\DI\Container
    {
        $package = $this->package;
        ($di = $package->extra['di'] ?? []) && $di = $package->file($di);
        return Php::di(
            [self::ID => $this->id, self::class => $this,],
            $di,
            Php\Composer\DIClassLoader::instance()->getContainer()
        );
    }

    /**
     * @see $cli
     * @return Php\Cli
     */
    protected function resolveCli(): Php\Cli
    {
        $cli = Oxidio\Functions::cli($this->package, $this->container);
        $this->container->set(get_class($cli), $cli);
        $this->container->set(self::CLI, $this->di(self::CLI, $cli));
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
        return new Blocks($this->di(self::BLOCKS, []));
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
     * @return Php\DI\Invoker
     */
    protected function resolveInvoker(): Php\DI\Invoker
    {
        return new Php\DI\Invoker(
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

        $fs->dumpFile($modulesDir . self::APP_TPL, implode(PHP_EOL, [
            '[{ $oView->renderApp() }]',
        ]));

        $fs->remove("{$path}/views/");
        $fs->remove("{$path}/menu.xml");

        foreach ($this->languages as $lang) {
            $fs->dumpFile("{$path}/views/admin/$lang/module_options.php", implode(PHP_EOL, [
                '<?php',
                sprintf('// autogenerated by %s ', __METHOD__),
                sprintf('$aLang = %s;', var_export(Php::arr($this->getTranslations($lang)), true)),
            ]));
        }

        foreach ($this->getBlocks() as $file => $block) {
            $fs->dumpFile("{$path}/{$file}", sprintf($block, $this->id));
        }

        $fs->dumpFile("{$path}/menu.xml", Php::map([
            '<?xml version="1.0" encoding="UTF-8"?>',
            sprintf('<!-- autogenerated by %s -->', __METHOD__),
            '<OX>',
            Php::map(Php::traverse($this->getMenu(), function (Menu $menu) {
                return (string)$menu;
            }))->string,
            '</OX>',
        ])->string);
    }

    private function params($key, MenuNode ...$nodes): void
    {
        foreach ($nodes as $node) {
            $node->params = Php::arr($node->params, function ($value) use ($key) {
                yield Php::isCallable($value) ? $value($this, $key) : $value;
            });
        }
    }

    /**
     * @param bool $flatten
     * @return Menu[]
     */
    public function getMenu(bool $flatten = false): array
    {
        return Php::flatten(Menu::generate($this->di(self::MENU, [])), function (
            Menu $menu,
            $key,
            Php\Map\Path $it
        ) use ($flatten) {
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
        return $this->di([
            self::ID => $this->id,
            self::TITLE => $this->id,
            self::DESCRIPTION => $this->package->description,
            self::URL => $this->package->homepage,
            self::VERSION => $this->package->version(),
            self::AUTHOR => $author['name'] ?? '',
            self::EMAIL => $author['email'] ?? '',
            self::EXTEND => []
        ]) + [
            self::SETTINGS => new Settings($this->di(self::SETTINGS, [])),
            self::BLOCKS => $this->getBlocks(),
            'events' => new Events,
            'templates' => [self::APP => self::APP_TPL],
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
        yield from (new Settings($this->di(self::SETTINGS, [])))->translate($lang);
        foreach ($this->getMenu(true) as $menu) {
            yield from $menu->translate($lang);
        }
    }
}
