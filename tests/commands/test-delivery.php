<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio;
use fn;

use Generator;
use OxidEsales\Eshop\Core\{
    Database\TABLE\OXCATEGORIES as CAT,
    Database\TABLE\OXCOUNTRY as COUNTRY,
    Database\TABLE\OXDEL2DELSET as D2S,
    Database\TABLE\OXDELIVERY as DEL,
    Database\TABLE\OXDELIVERYSET as SET,
    Database\TABLE\OXOBJECT2DELIVERY as O2D,
    Database\TABLE\OXOBJECT2GROUP as O2G,
    Database\TABLE\OXOBJECT2PAYMENT as O2P,
    Database\TABLE\OXPAYMENTS,
    Database\TABLE as TAB
};
use Oxidio\Core\Row;
use Oxidio\Core\Shop;

/**
 * @param Shop|string $shop
 * @param bool $commit
 * @return Generator
 */
return static function (Core\Shop $shop, bool $commit = false): Generator {
    $eu = ['BE', 'DK', 'FI', 'FR', 'IT', 'LI', 'LU', 'NL', 'AT', 'PL', 'SE', 'HU'];
    $de = ['DE'];
    $ch = ['CH'];
    $no = ['NO'];

    $cats = function (...$args) use ($shop) {
        static $data;
        if ($data === null) {
            $data = fn\traverse($shop->query(TAB\OXCATEGORIES, function(Row $row) {
                return fn\mapKey($row[CAT\OXID])->andValue($row);
            }));
            /** @var Row $row */
            foreach ($data as $row) {
                $parent = $row;
                $path = [];
                do {
                    $path[] = strtolower($shop::seo($parent[CAT\OXTITLE]));
                } while ($parent = $data[$parent[CAT\OXPARENTID]] ?? null);
                $data[implode('/', array_reverse($path))] = $row;
            }
        }
        foreach ($args as $arg) {
            ($id = $data[$arg][CAT\OXID] ?? null) && yield $arg => $id;
        }
    };

    $countries = $shop->query(TAB\OXCOUNTRY, static function (Row $row) {
        return $row(COUNTRY\OXISOALPHA2, COUNTRY\OXID);
    }, [COUNTRY\OXISOALPHA2 => ['IN', fn\merge($eu, $de, $ch, $no)]]);
    $shop->modify(TAB\OXCOUNTRY)->update([COUNTRY\OXACTIVE => false]);
    $shop->modify(TAB\OXCOUNTRY)->update([COUNTRY\OXACTIVE => true], [COUNTRY\OXID => ['IN', $countries]]);

    $payment = function(array $record) {
        return $record + [
            OXPAYMENTS\OXADDSUM      => 0,
            OXPAYMENTS\OXFROMAMOUNT  => 0,
            OXPAYMENTS\OXTOAMOUNT    => 1000000,
            OXPAYMENTS\OXADDSUMTYPE  => 'abs',
            OXPAYMENTS\OXADDSUMRULES => 15,
            OXPAYMENTS\OXACTIVE      => true,
            OXPAYMENTS\OXCHECKED     => false,
            'countries'              => [],
            'groups'                 => [],
        ];
    };

    $payments = [
        'oxidcashondel' => $payment([
            OXPAYMENTS\OXDESC => 'Nachnahme',
            OXPAYMENTS\OXADDSUM => 5,
            OXPAYMENTS\OXTOAMOUNT => 2500,
            'countries' => $de
        ]),
        'oxidpayadvance' => $payment([
            OXPAYMENTS\OXDESC => 'Vorkasse',
            OXPAYMENTS\OXCHECKED => true
        ]),
        'oxidinvoice' => $payment([
            OXPAYMENTS\OXDESC => 'Rechnung',
            'groups' => ['oxiddealer'],
        ]),
        'oxempty' => $payment([
            OXPAYMENTS\OXDESC => 'Empty',
            OXPAYMENTS\OXTOAMOUNT => 0
        ]),
        'payppaypalplus' => $payment([
            OXPAYMENTS\OXDESC => 'PayPal Plus',
            OXPAYMENTS\OXTOAMOUNT => 10000
        ]),
        'oxidpaypal' => $payment([
            OXPAYMENTS\OXDESC => 'PayPal',
            OXPAYMENTS\OXTOAMOUNT => 99999
        ]),
        'klarna_pay_now' => $payment([
            OXPAYMENTS\OXDESC => 'Sofort bezahlen'
        ]),
        'klarna_pay_later' => $payment([
            OXPAYMENTS\OXDESC => 'Klarna Rechnung',
            'countries' => $de
        ]),
        'klarna_slice_it' => $payment([
            OXPAYMENTS\OXDESC => 'Klarna Ratenkauf',
            OXPAYMENTS\OXACTIVE => false,
        ]),
        'PAYMENT_CREDIT_PURCHASE' => $payment([
            OXPAYMENTS\OXDESC => 'Zielkauf',
            OXPAYMENTS\OXFROMAMOUNT => 250,
            'countries' => $de
        ]),
        'PAYMENT_FINANCING' => $payment([
            OXPAYMENTS\OXDESC => 'Finanzierung',
            OXPAYMENTS\OXFROMAMOUNT => 250,
            'countries' => $de
        ]),
    ];

    $sets = [
        'DS_HAULER_DE' => [
            SET\OXTITLE => 'Spedition (DE)',
            SET\OXPOS => 100,
            SET\OXACTIVE => true,
            'countries' => $de,
            'rules' => [
                'FREE' => [
                    DEL\OXTITLE => 'Spedition DE -> Portofrei',
                    DEL\OXSORT => 110,
                    'categories' => $cats('kiteboarding/kites'),
                ],
            ],
        ],
        'DS_HAULER_EU' => [
            SET\OXTITLE => 'Spedition (EU)',
            SET\OXPOS => 200,
            SET\OXACTIVE => true,
            'countries' => $eu,
            'rules' => [
                'PORTO' => [
                    DEL\OXTITLE => 'Spedition EU -> 50 € Porto',
                    DEL\OXSORT => 210,
                    DEL\OXADDSUM => 50,
                    'categories' => $cats('kiteboarding/kites'),
                ],
            ],
        ],
        'DS_HAULER_CH' => [
            SET\OXTITLE => 'Spedition (CH)',
            SET\OXPOS => 300,
            SET\OXACTIVE => true,
            'countries' => $ch,
            'rules' => [
                'PORTO' => [
                    DEL\OXTITLE => 'Spedition CH -> 170 € Porto',
                    DEL\OXSORT => 310,
                    DEL\OXADDSUM => 170,
                    'categories' => $cats('kiteboarding/kites'),
                ],
            ],
        ],
        'DS_HAULER_NO' => [
            SET\OXTITLE => 'Spedition (NO)',
            SET\OXPOS => 400,
            SET\OXACTIVE => true,
            'countries' => $no,
            'rules' => [
                'PORTO' => [
                    DEL\OXTITLE => 'Spedition NO -> 170 € Porto',
                    DEL\OXSORT => 410,
                    DEL\OXADDSUM => 170,
                    'categories' => $cats('kiteboarding/kites'),
                ],
            ],
        ],
        'oxidstandard' => [
            SET\OXTITLE => 'DHL Paket (DE)',
            SET\OXPOS => 500,
            SET\OXACTIVE => true,
            'countries' => $de,
            'rules' => [
                'PORTO' => [
                    DEL\OXTITLE => 'DHL DE bis 30 € -> 5 € Porto',
                    DEL\OXSORT => 510,
                    DEL\OXADDSUM => 5,
                    DEL\OXPARAM => 0,
                    DEL\OXPARAMEND => 29.99,
                ],
                'FREE' => [
                    DEL\OXTITLE => 'DHL DE ab 30 € -> Portofrei',
                    DEL\OXSORT => 520,
                    DEL\OXADDSUM => 0,
                    DEL\OXPARAM => 30,
                    DEL\OXPARAMEND => 1000000,
                ],
            ],
        ],
        'DS_DHL_EU' => [
            SET\OXTITLE => 'DHL Paket (EU)',
            SET\OXPOS => 600,
            SET\OXACTIVE => true,
            'countries' => $eu,
            'rules' => [
                'PORTO' => [
                    DEL\OXTITLE => 'DHL EU bis 100 € -> 10 € Porto',
                    DEL\OXSORT => 610,
                    DEL\OXADDSUM => 10,
                    DEL\OXPARAM => 0,
                    DEL\OXPARAMEND => 99.99,
                ],
                'FREE' => [
                    DEL\OXTITLE => 'DHL EU ab 100 € -> Portofrei',
                    DEL\OXSORT => 620,
                    DEL\OXADDSUM => 0,
                    DEL\OXPARAM => 100,
                    DEL\OXPARAMEND => 1000000,
                ],
            ],
        ],
        'DS_DHL_CH' => [
            SET\OXTITLE => 'DHL Paket (CH)',
            SET\OXPOS => 700,
            SET\OXACTIVE => true,
            'countries' => $ch,
            'rules' => [
                'PORTO' => [
                    DEL\OXTITLE => 'DHL CH -> 24,37 € Porto',
                    DEL\OXSORT => 710,
                    DEL\OXADDSUM => 24.37,
                ],
            ],
        ],
        'DS_DHL_NO' => [
            SET\OXTITLE => 'DHL Paket (NO)',
            SET\OXPOS => 800,
            SET\OXACTIVE => true,
            'countries' => $no,
            'rules' => [
                'PORTO' => [
                    DEL\OXTITLE => 'DHL NO -> 29,41 € Porto',
                    DEL\OXSORT => 810,
                    DEL\OXADDSUM => 29.41,
                ],
            ],
        ],
    ];

    $shop->modify(TAB\OXPAYMENTS)->update([OXPAYMENTS\OXACTIVE => false]);
    $shop->modify(TAB\OXPAYMENTS)->replace($payments, OXPAYMENTS\OXID);
    $o2p = [];
    $o2g = [];
    foreach ($payments as $id => ['countries' => $codes, 'groups' => $groups]) {
        foreach ($codes as $country) {
            $o2p["{$id}_{$country}"] = [
                O2P\OXPAYMENTID => $id,
                O2P\OXOBJECTID => $countries[$country] ?? null,
                O2P\OXTYPE => TAB\OXCOUNTRY,
            ];
        }
        foreach (fn\keys($sets) as $setId) {
            $o2p["{$id}_{$setId}"] = [
                O2P\OXPAYMENTID => $id,
                O2P\OXOBJECTID => $setId,
                O2P\OXTYPE => 'oxdelset',
            ];
        }

        foreach ($groups as $group) {
            $o2g["{$id}_{$group}"] = [
                O2G\OXOBJECTID => $id,
                O2G\OXGROUPSID => $group,
            ];
        }
    }
    $shop->modify(TAB\OXOBJECT2PAYMENT)->delete(
        [O2P\OXPAYMENTID => ['IN', fn\keys($payments)]],
        [O2P\OXOBJECTID => ['IN', fn\keys($sets)]]
    );
    $shop->modify(TAB\OXOBJECT2PAYMENT)->replace($o2p, O2P\OXID);

    $shop->modify(TAB\OXOBJECT2GROUP)->delete([O2G\OXOBJECTID => ['IN', fn\keys($payments)]]);
    $shop->modify(TAB\OXOBJECT2GROUP)->replace($o2g, O2G\OXID);

    $shop->modify(TAB\OXDELIVERYSET)->update([SET\OXACTIVE => false]);
    $shop->modify(TAB\OXDELIVERYSET)->replace($sets, SET\OXID);

    $s2c = [];
    $del = [];
    $d2s = [];
    foreach ($sets as $setId => ['countries' => $codes, 'rules' => $rules]) {
        foreach ($codes as $country) {
            $s2c["{$setId}_{$country}"] = [
                O2D\OXDELIVERYID => $setId,
                O2D\OXOBJECTID => $countries[$country] ?? null,
                O2D\OXTYPE => 'oxdelset',
            ];
        }
        foreach ($rules as $suffix => $rule) {
            $del[$delId = "{$setId}:{$suffix}"] = $rule + [
                DEL\OXACTIVE => true,
                DEL\OXFINALIZE => true,
                DEL\OXADDSUMTYPE => 'abs',
                DEL\OXDELTYPE => 'p',
                DEL\OXPARAM => 0,
                DEL\OXPARAMEND => 1000000,
            ];
            $d2s[$delId] = [
               D2S\OXDELID => $delId,
               D2S\OXDELSETID => $setId,
            ];
            foreach ($rule['categories'] ?? [] as $catId) {
                $s2c[$delId . $catId] = [
                    O2D\OXDELIVERYID => $delId,
                    O2D\OXOBJECTID => $catId,
                    O2D\OXTYPE => TAB\OXCATEGORIES,
                ];
            }
        }
    }
    $shop->modify(TAB\OXOBJECT2DELIVERY)->delete([O2D\OXDELIVERYID => ['IN', fn\keys($sets, $del)]]);
    $shop->modify(TAB\OXOBJECT2DELIVERY)->replace($s2c, O2D\OXID);

    $shop->modify(TAB\OXDELIVERY)->update([DEL\OXACTIVE => false]);
    $shop->modify(TAB\OXDELIVERY)->replace($del, DEL\OXID);


    $shop->modify(TAB\OXDEL2DELSET)->delete([D2S\OXDELSETID => ['IN', fn\keys($sets)]]);
    $shop->modify(TAB\OXDEL2DELSET)->replace($d2s, D2S\OXID);

    foreach ($shop->commit($commit) as $result) {
        yield fn\io((object)$result, fn\Cli\IO::VERBOSITY_VERBOSE);
    }
};
