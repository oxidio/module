<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio;

use Generator;
use Oxidio\Enum\Tables as T;

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
            T\PAYMENTS::ADDSUM      => 0,
            T\PAYMENTS::FROMAMOUNT  => 0,
            T\PAYMENTS::TOAMOUNT    => 1000000,
            T\PAYMENTS::ADDSUMTYPE  => 'abs',
            T\PAYMENTS::ADDSUMRULES => 15,
            T\PAYMENTS::ACTIVE      => true,
            T\PAYMENTS::CHECKED     => false,
            'groups'                 => [],
        ];
    };

    $ds = new Cli\Setup\DeliverySets([
        'DS_HAULER_DE' => [
            T\DELIVERYSET::TITLE => 'Spedition (DE)',
            T\DELIVERYSET::POS => 100,
            T\DELIVERYSET::ACTIVE => true,
            'countries' => $de,
            'rules' => [
                'FREE' => [
                    T\DELIVERY::TITLE => 'Spedition DE -> Portofrei',
                    T\DELIVERY::SORT => 110,
                    'categories' => ['kiteboarding/kites'],
                ],
            ],
        ],
        'DS_HAULER_EU' => [
            T\DELIVERYSET::TITLE => 'Spedition (EU)',
            T\DELIVERYSET::POS => 200,
            T\DELIVERYSET::ACTIVE => true,
            'countries' => $eu,
            'rules' => [
                'PORTO' => [
                    T\DELIVERY::TITLE => 'Spedition EU -> 50 € Porto',
                    T\DELIVERY::SORT => 210,
                    T\DELIVERY::ADDSUM => 50,
                    'categories' => ['kiteboarding/kites'],
                ],
            ],
        ],
        'DS_HAULER_CH' => [
            T\DELIVERYSET::TITLE => 'Spedition (CH)',
            T\DELIVERYSET::POS => 300,
            T\DELIVERYSET::ACTIVE => true,
            'countries' => $ch,
            'rules' => [
                'PORTO' => [
                    T\DELIVERY::TITLE => 'Spedition CH -> 170 € Porto',
                    T\DELIVERY::SORT => 310,
                    T\DELIVERY::ADDSUM => 170,
                    'categories' => ['kiteboarding/kites'],
                ],
            ],
        ],
        'DS_HAULER_NO' => [
            T\DELIVERYSET::TITLE => 'Spedition (NO)',
            T\DELIVERYSET::POS => 400,
            T\DELIVERYSET::ACTIVE => true,
            'countries' => $no,
            'rules' => [
                'PORTO' => [
                    T\DELIVERY::TITLE => 'Spedition NO -> 170 € Porto',
                    T\DELIVERY::SORT => 410,
                    T\DELIVERY::ADDSUM => 170,
                    'categories' => ['kiteboarding/kites'],
                ],
            ],
        ],
        'oxidstandard' => [
            T\DELIVERYSET::TITLE => 'DHL Paket (DE)',
            T\DELIVERYSET::POS => 500,
            T\DELIVERYSET::ACTIVE => true,
            'countries' => $de,
            'rules' => [
                'PORTO' => [
                    T\DELIVERY::TITLE => 'DHL DE bis 30 € -> 5 € Porto',
                    T\DELIVERY::SORT => 510,
                    T\DELIVERY::ADDSUM => 5,
                    T\DELIVERY::PARAM => 0,
                    T\DELIVERY::PARAMEND => 29.99,
                ],
                'FREE' => [
                    T\DELIVERY::TITLE => 'DHL DE ab 30 € -> Portofrei',
                    T\DELIVERY::SORT => 520,
                    T\DELIVERY::ADDSUM => 0,
                    T\DELIVERY::PARAM => 30,
                    T\DELIVERY::PARAMEND => 1000000,
                ],
            ],
        ],
        'DS_DHL_EU' => [
            T\DELIVERYSET::TITLE => 'DHL Paket (EU)',
            T\DELIVERYSET::POS => 600,
            T\DELIVERYSET::ACTIVE => true,
            'countries' => $eu,
            'rules' => [
                'PORTO' => [
                    T\DELIVERY::TITLE => 'DHL EU bis 100 € -> 10 € Porto',
                    T\DELIVERY::SORT => 610,
                    T\DELIVERY::ADDSUM => 10,
                    T\DELIVERY::PARAM => 0,
                    T\DELIVERY::PARAMEND => 99.99,
                ],
                'FREE' => [
                    T\DELIVERY::TITLE => 'DHL EU ab 100 € -> Portofrei',
                    T\DELIVERY::SORT => 620,
                    T\DELIVERY::ADDSUM => 0,
                    T\DELIVERY::PARAM => 100,
                    T\DELIVERY::PARAMEND => 1000000,
                ],
            ],
        ],
        'DS_DHL_CH' => [
            T\DELIVERYSET::TITLE => 'DHL Paket (CH)',
            T\DELIVERYSET::POS => 700,
            T\DELIVERYSET::ACTIVE => true,
            'countries' => $ch,
            'rules' => [
                'PORTO' => [
                    T\DELIVERY::TITLE => 'DHL CH -> 24,37 € Porto',
                    T\DELIVERY::SORT => 710,
                    T\DELIVERY::ADDSUM => 24.37,
                ],
            ],
        ],
        'DS_DHL_NO' => [
            T\DELIVERYSET::TITLE => 'DHL Paket (NO)',
            T\DELIVERYSET::POS => 800,
            T\DELIVERYSET::ACTIVE => true,
            'countries' => $no,
            'rules' => [
                'PORTO' => [
                    T\DELIVERY::TITLE => 'DHL NO -> 29,41 € Porto',
                    T\DELIVERY::SORT => 810,
                    T\DELIVERY::ADDSUM => 29.41,
                ],
            ],
        ],
    ], [
        'oxidcashondel' => $payment([
            T\PAYMENTS::DESC => 'Nachnahme',
            T\PAYMENTS::ADDSUM => 5,
            T\PAYMENTS::TOAMOUNT => 2500,
            'countries' => $de
        ]),
        'oxidpayadvance' => $payment([
            T\PAYMENTS::DESC => 'Vorkasse',
            T\PAYMENTS::CHECKED => true
        ]),
        'oxidinvoice' => $payment([
            T\PAYMENTS::DESC => 'Rechnung',
            'groups' => ['oxiddealer'],
        ]),
        'oxempty' => $payment([
            T\PAYMENTS::DESC => 'Empty',
            T\PAYMENTS::TOAMOUNT => 0
        ]),
        'payppaypalplus' => $payment([
            T\PAYMENTS::DESC => 'PayPal Plus',
            T\PAYMENTS::TOAMOUNT => 10000
        ]),
        'oxidpaypal' => $payment([
            T\PAYMENTS::DESC => 'PayPal',
            T\PAYMENTS::TOAMOUNT => 99999
        ]),
        'klarna_pay_now' => $payment([
            T\PAYMENTS::DESC => 'Sofort bezahlen'
        ]),
        'klarna_pay_later' => $payment([
            T\PAYMENTS::DESC => 'Klarna Rechnung',
            'countries' => $de
        ]),
        'klarna_slice_it' => $payment([
            T\PAYMENTS::DESC => 'Klarna Ratenkauf',
            T\PAYMENTS::ACTIVE => false,
        ]),
        'PAYMENT_CREDIT_PURCHASE' => $payment([
            T\PAYMENTS::DESC => 'Zielkauf',
            T\PAYMENTS::FROMAMOUNT => 250,
            'countries' => $de
        ]),
        'PAYMENT_FINANCING' => $payment([
            T\PAYMENTS::DESC => 'Finanzierung',
            T\PAYMENTS::FROMAMOUNT => 250,
            'countries' => $de
        ]),
    ]);

    yield from $ds($shop, $commit);
};
