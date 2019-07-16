<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio;

use Generator;
use OxidEsales\Eshop\Core\{
    Database\TABLE\OXDELIVERY as DEL,
    Database\TABLE\OXDELIVERYSET as SET,
    Database\TABLE\OXPAYMENTS
};

/**
 * @param Core\Shop $shop
 * @param bool $commit
 * @return Generator
 */
return static function (Core\Shop $shop, bool $commit = false): Generator {
    $eu = ['BE', 'DK', 'FI', 'FR', 'IT', 'LI', 'LU', 'NL', 'AT', 'PL', 'SE', 'HU'];
    $de = ['DE'];
    $ch = ['CH'];
    $no = ['NO'];

    $payment = function(array $record) {
        return $record + [
            OXPAYMENTS\OXADDSUM      => 0,
            OXPAYMENTS\OXFROMAMOUNT  => 0,
            OXPAYMENTS\OXTOAMOUNT    => 1000000,
            OXPAYMENTS\OXADDSUMTYPE  => 'abs',
            OXPAYMENTS\OXADDSUMRULES => 15,
            OXPAYMENTS\OXACTIVE      => true,
            OXPAYMENTS\OXCHECKED     => false,
            'groups'                 => [],
        ];
    };

    $ds = new Cli\Setup\DeliverySets([
        'DS_HAULER_DE' => [
            SET\OXTITLE => 'Spedition (DE)',
            SET\OXPOS => 100,
            SET\OXACTIVE => true,
            'countries' => $de,
            'rules' => [
                'FREE' => [
                    DEL\OXTITLE => 'Spedition DE -> Portofrei',
                    DEL\OXSORT => 110,
                    'categories' => ['kiteboarding/kites'],
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
                    'categories' => ['kiteboarding/kites'],
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
                    'categories' => ['kiteboarding/kites'],
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
                    'categories' => ['kiteboarding/kites'],
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
    ], [
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
    ]);

    yield from $ds($shop, $commit);
};
