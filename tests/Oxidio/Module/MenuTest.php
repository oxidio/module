<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Module;

use fn\test\assert;
use Oxidio;

/**
 */
class MenuTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructor(): void
    {
        assert\type(Menu::class, menu('label'));
        assert\same('label', menu('label')->label);
    }

    public function testGetId(): void
    {
        assert\same('label', menu('label')->getId());
        assert\same('de', menu(['de', 'en'])->getId());
        assert\same('en', menu(['en', 'de'])->getId());
    }

    public function testToString(): void
    {
        self::assertToString([
            '    <OXMENU id="label">',
            '    </OXMENU>',
        ], menu('label'));

        self::assertToString([
            '    <OXMENU id="menu">',
            '        <MAINMENU id="main">',
            '            <SUBMENU id="sub">',
            '            </SUBMENU>',
            '        </MAINMENU>',
            '    </OXMENU>',
        ], menu('menu', menu('main', menu('sub'))));
    }

    private static function assertToString(array $lines, Menu $menu): void
    {
        assert\same(implode(PHP_EOL, $lines), (string) $menu);
    }

}
