<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Module;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\ArticleList;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\SeoDecoder;
use OxidEsales\Eshop\Core\Theme;
use OxidEsales\Eshop\Core\ViewConfig;
use Oxidio\DI\SmartyTemplateVars;

use Php;
use DI;
use Oxidio;
use Smarty;

return [
    Module::TITLE => 'bar module (oxidio)',

    Module::SETTINGS => [
        'foo' => [
            'string' => [Settings::VALUE => 'string'],
            'true'   => [Settings::VALUE => true],
            'false'  => [Settings::VALUE => false],
            'aarr'   => [Settings::VALUE => ['a' => 'A', 'b' => 'B']],
        ],
        'bar' => [
            'selected' => [Settings::VALUE => ['c' => 'C', 'd' => 'D', 'e' => 'E'], Settings::SELECTED => 'd']
        ]
    ],

    Module::EXTEND => [
        SeoDecoder::class => Oxidio\Bar\Core\BarSeoDecoder::class,
    ],

    Module::BLOCKS => [
        Theme\LAYOUT_BASE   => [
            Theme\LAYOUT_BASE\BLOCK_HEAD_META_ROBOTS  => Block::prepend(function () {

            }),
            Theme\LAYOUT_BASE\BLOCK_HEAD_TITLE => Block::overwrite(function (
                FrontendController $ctrl,
                SmartyTemplateVars $vars,
                Smarty $smarty,
                Config $configFromRegistry,
                SeoDecoder $decoder,
                Article $default = null,
                ArticleList ...$lists
            ) {
                return implode('-', [
                    get_class($ctrl),
                    get_class($vars),
                    get_class($smarty),
                    get_class($configFromRegistry),
                    get_class($decoder),
                    $default ? get_class($default) : '',
                    count($lists)
                ]);
            }),
        ],
        Theme\LAYOUT_FOOTER => [
            Theme\LAYOUT_FOOTER\BLOCK_MAIN => Block::append(function () {
            }),
        ],
    ],

    Oxidio\Bar\Cli\Db::class => DI\create(),
    Oxidio\Bar\Cli\Shop::class => DI\create(),

    Module::MENU => [
        Menu::ADMIN => [ // merge
            Menu::create(['admin-main'], [ // register new main menu under ADMIN
                admin\main\sub1::class => Menu::create(['label' => 'admin-main-sub1']),
                admin\main\sub2::class => Menu::create('admin-main-sub2', [
                    admin\main\sub2\t1::class => 'admin-main-sub2-t1',
                    'admin-main-sub2-btn1',
                    'admin-main-sub2-btn2',
                ]),
            ]),

            Menu::ADMIN_USERS => [
                // register new sub menus under ADMIN/USERS
                admin\users\sub1::class => Menu::create(['admin-users-sub1', 'list' => 'user_list', 'groups' => ['g1', 'g2'], 'rights' => ['r1']]),
                admin\users\sub2::class => Menu::create('admin-users-sub2', [
                    admin\users\sub2\t1::class => 'admin-users-sub2-t1',
                    admin\users\sub2\t2::class => 'admin-users-sub2-t2',
                    'admin-users-sub2-btn1',
                ]),

                Menu::ADMIN_USERS_GROUPS => [ // register new tabs and buttons under ADMIN/USERS/GROUPS,
                    admin\users\groups\t1::class => 'admin-users-groups-t1',
                    admin\users\groups\t2::class => ['de' => 'admin-users-groups-t2-de', 'en' => 'admin-users-groups-t2-en'],
                    'admin-users-groups-btn1',
                    'admin-users-groups-btn2',
                ],
            ],
        ],

        Menu::create(['bar'], // create
            Menu::create('bar-main', [ // create new main menu under BAR
                bar\main\sub1::class => Menu::create('bar-main-sub1', [
                    bar\main\sub1\t1::class => ['bar-main-sub1-t1', 'params' => ['a' => 'b', 'c' => ['d', 'e']]],
                    'bar-main-sub1-btn1',
                    ['label' => 'bar-main-sub1-btn2', 'class' => bar\main\sub1\btn2::class],
                ]),
                App::menu('bar-app', function (SmartyTemplateVars $vars, App $ctrl, Config $config, ViewConfig $vc) {
                    return '<h2>' . implode('-', [
                            'bar-app',
                            get_class($ctrl),
                            get_class($vars),
                            get_class($config),
                            get_class($vc),
                        ]) . '</h2>';
                })
            ]),
            [Menu::create(['bar-users', 'params' => ['bar' => 'user'], 'class' => bar\users::class])]
        ),

        foo::class => Menu::create('foo'),
    ],
];
