<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Cli;

use php;
use Oxidio\Core;
use Oxidio\Enum\Tables as T;

class ShopConfig
{
    private const CLEAN = 'clean';
    private const UPDATE = 'update';

    /**
     * @var Core\Shop\Config
     */
    private $config;

    public function __construct(Core\Shop\Config $config = null)
    {
        $this->config = $config ?: new Core\Shop\Config;
    }

    /**
     * Show/modify shop configuration
     *
     * @param php\Cli\IO $io
     * @param Core\Shop $shop
     * @param bool $dryRun
     * @param string $action update|clean
     */
    public function __invoke(php\Cli\IO $io, Core\Shop $shop, bool $dryRun = false, string $action = null): void
    {
        $table = [];
        foreach ($this->config->diff([Core\Extension::SHOP => $shop->config]) as $name => [$value, $diff, $module]) {
            if ($diff !== false) {
                $name = $diff ? "<error>$name</error>" : "<info>$name</info>";
            }
            $table[] = ['module' => $module, 'entry' => $name, 'value' => $value, 'diff' => $diff];
        }
        (new php\Cli\Renderable($table))->toCli($io);

        $io->isVeryVerbose() && (new php\Cli\Renderable(php\map($shop->config, static function ($value, $name) {
            return "'$name' => " . (is_array($value) ? new php\ArrayExport($value) : var_export($value, true)) . ',';
        })->string))->toCli($io);

        if ($action === self::CLEAN) {
            $shop([T::TPLBLOCKS => null, T::CONFIGDISPLAY => null, T::CONFIG => null]);
            $shop($this->config);
        } else if ($action === self::UPDATE) {
            $shop($this->config);
        }

        foreach ($shop->commit(!$dryRun) as $item) {
            $io->isVerbose() && (new php\Cli\Renderable((object)$item))->toCli($io);
        }
    }
}
