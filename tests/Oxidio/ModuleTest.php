<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio;

use fn\test\assert;
use Oxidio\Module\Settings as S;

class TestModule extends Module
{
    protected const CONFIG = TEST;
}

/**
 */
class ModuleTest extends \PHPUnit_Framework_TestCase
{
    public function testInstance(): void
    {
        assert\type(TestModule::class, $module = TestModule::instance());
        assert\same($module, Module::instance(TEST));
        assert\not\same($module, Module::instance(TEST_EMPTY));
    }

    public function testJsonSerialize(): void
    {
        assert\equals([
            Module\ID       => null,
            Module\TITLE    => null,
            Module\URL      => null,
            Module\AUTHOR   => null,
            Module\SETTINGS => [],
            Module\BLOCKS   => [],
        ], json_decode(json_encode(Module::instance(TEST_EMPTY)), true));

        assert\equals([
            Module\ID       => Module\ID,
            Module\TITLE    => Module\TITLE,
            Module\URL      => Module\URL,
            Module\AUTHOR   => Module\AUTHOR,
            Module\SETTINGS => [
                [S\GROUP => 'group', S\NAME => 'string', S\VALUE => 'string', S\TYPE => 'str'],
                [S\GROUP => 'group', S\NAME => 'true', S\VALUE => 'true', S\TYPE => 'bool'],
                [S\GROUP => 'group', S\NAME => 'false', S\VALUE => 'false', S\TYPE => 'bool'],
                [S\GROUP => 'group', S\NAME => 'select', S\VALUE => ['a', 'b', 'c'], S\TYPE => 'aarr'],
                [S\GROUP => 'group', S\NAME => 'selected', S\VALUE => 'a', S\TYPE => 'select', S\SELECTED => 'a', 'constraints' => 'a|b|c'],
            ],
            Module\BLOCKS   => [
                ['template' => 't1.tpl', 'block' => 'b1', 'file' => 'views/blocks/t1-b1.tpl'],
                ['template' => 't2.tpl', 'block' => 'b2', 'file' => 'views/blocks/t2-b2.tpl'],
                ['template' => 't2.tpl', 'block' => 'b3', 'file' => 'views/blocks/b3.tpl'],
            ],
        ], json_decode(json_encode(Module::instance(TEST)), true));

    }
}
