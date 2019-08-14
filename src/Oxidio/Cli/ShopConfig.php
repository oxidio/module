<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Cli;

use php;
use OxidEsales\Eshop\Core\Database\TABLE;
use Oxidio\Core\Shop;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;

class ShopConfig
{
    private const DIFF = [1 => '+ ', 2 => '- '];

    /**
     * Show/modify shop configuration
     *
     * @param php\Cli\IO $io
     * @param Shop $shop
     * @param bool $clean
     */
    public function __invoke(php\Cli\IO $io, Shop $shop, bool $clean)
    {
        $differ = new Differ(new UnifiedDiffOutputBuilder('', false));
        $table = php\traverse($shop->config, static function ($value, $name) use ($differ) {
            $converted = self::convert($value);

            $lines = [];
            if (php\hasKey($name, Shop\Config::INITIAL)) {
                $diff = 0;
                foreach ($differ->diffToArray(self::convert(Shop\Config::INITIAL[$name]), $converted) as $line) {
                    $diff += $line[1];
                    $lines[] = (self::DIFF[$line[1]] ?? '  ') . $line[0];
                }
                if ($diff) {
                    $name = "<error>$name</error>";
                } else {
                    $name = "<info>$name</info>";
                    $lines = [];
                }
            }
            return [
                'entry' => $name,
                'value' => $converted,
                'diff'  => implode(is_array($value) ? '' : PHP_EOL, $lines),
            ];
        });
        php\io($table)->toCli($io);

        $io->isVeryVerbose() && php\io(php\map($shop->config, static function ($value, $name) {
            return "'$name' => " . (is_array($value) ? new php\ArrayExport($value) : var_export($value, true)) . ',';
        })->string)->toCli($io);


        if ($clean) {
            $shop([TABLE\OXTPLBLOCKS => null, TABLE\OXCONFIGDISPLAY => null, TABLE\OXCONFIG => null]);
            $shop(new Shop\Config(['' => Shop\Config::INITIAL]));
        }

        foreach ($shop->commit() as $item) {
            $io->isVerbose() && php\io((object)$item)->toCli($io);
        }
    }

    private static function convert($value)
    {
        return is_string($value) ? $value : json_encode($value, JSON_PRETTY_PRINT);
    }
}
