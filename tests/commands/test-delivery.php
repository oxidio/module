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
            T\Payments::ADDSUM      => 0,
            T\Payments::FROMAMOUNT  => 0,
            T\Payments::TOAMOUNT    => 1000000,
            T\Payments::ADDSUMTYPE  => 'abs',
            T\Payments::ADDSUMRULES => 15,
            T\Payments::ACTIVE      => true,
            T\Payments::CHECKED     => false,
            'groups'                 => [],
        ];
    };

    $ds = new Cli\Setup\DeliverySets([
        'DS_HAULER_DE' => [
            T\Deliveryset::TITLE => 'Spedition (DE)',
            T\Deliveryset::POS => 100,
            T\Deliveryset::ACTIVE => true,
            'countries' => $de,
            'rules' => [
                'FREE' => [
                    T\Delivery::TITLE => 'Spedition DE -> Portofrei',
                    T\Delivery::SORT => 110,
                    'categories' => ['kiteboarding/kites'],
                ],
            ],
        ],
        'DS_HAULER_EU' => [
            T\Deliveryset::TITLE => 'Spedition (EU)',
            T\Deliveryset::POS => 200,
            T\Deliveryset::ACTIVE => true,
            'countries' => $eu,
            'rules' => [
                'PORTO' => [
                    T\Delivery::TITLE => 'Spedition EU -> 50 € Porto',
                    T\Delivery::SORT => 210,
                    T\Delivery::ADDSUM => 50,
                    'categories' => ['kiteboarding/kites'],
                ],
            ],
        ],
        'DS_HAULER_CH' => [
            T\Deliveryset::TITLE => 'Spedition (CH)',
            T\Deliveryset::POS => 300,
            T\Deliveryset::ACTIVE => true,
            'countries' => $ch,
            'rules' => [
                'PORTO' => [
                    T\Delivery::TITLE => 'Spedition CH -> 170 € Porto',
                    T\Delivery::SORT => 310,
                    T\Delivery::ADDSUM => 170,
                    'categories' => ['kiteboarding/kites'],
                ],
            ],
        ],
        'DS_HAULER_NO' => [
            T\Deliveryset::TITLE => 'Spedition (NO)',
            T\Deliveryset::POS => 400,
            T\Deliveryset::ACTIVE => true,
            'countries' => $no,
            'rules' => [
                'PORTO' => [
                    T\Delivery::TITLE => 'Spedition NO -> 170 € Porto',
                    T\Delivery::SORT => 410,
                    T\Delivery::ADDSUM => 170,
                    'categories' => ['kiteboarding/kites'],
                ],
            ],
        ],
        'oxidstandard' => [
            T\Deliveryset::TITLE => 'DHL Paket (DE)',
            T\Deliveryset::POS => 500,
            T\Deliveryset::ACTIVE => true,
            'countries' => $de,
            'rules' => [
                'PORTO' => [
                    T\Delivery::TITLE => 'DHL DE bis 30 € -> 5 € Porto',
                    T\Delivery::SORT => 510,
                    T\Delivery::ADDSUM => 5,
                    T\Delivery::PARAM => 0,
                    T\Delivery::PARAMEND => 29.99,
                ],
                'FREE' => [
                    T\Delivery::TITLE => 'DHL DE ab 30 € -> Portofrei',
                    T\Delivery::SORT => 520,
                    T\Delivery::ADDSUM => 0,
                    T\Delivery::PARAM => 30,
                    T\Delivery::PARAMEND => 1000000,
                ],
            ],
        ],
        'DS_DHL_EU' => [
            T\Deliveryset::TITLE => 'DHL Paket (EU)',
            T\Deliveryset::POS => 600,
            T\Deliveryset::ACTIVE => true,
            'countries' => $eu,
            'rules' => [
                'PORTO' => [
                    T\Delivery::TITLE => 'DHL EU bis 100 € -> 10 € Porto',
                    T\Delivery::SORT => 610,
                    T\Delivery::ADDSUM => 10,
                    T\Delivery::PARAM => 0,
                    T\Delivery::PARAMEND => 99.99,
                ],
                'FREE' => [
                    T\Delivery::TITLE => 'DHL EU ab 100 € -> Portofrei',
                    T\Delivery::SORT => 620,
                    T\Delivery::ADDSUM => 0,
                    T\Delivery::PARAM => 100,
                    T\Delivery::PARAMEND => 1000000,
                ],
            ],
        ],
        'DS_DHL_CH' => [
            T\Deliveryset::TITLE => 'DHL Paket (CH)',
            T\Deliveryset::POS => 700,
            T\Deliveryset::ACTIVE => true,
            'countries' => $ch,
            'rules' => [
                'PORTO' => [
                    T\Delivery::TITLE => 'DHL CH -> 24,37 € Porto',
                    T\Delivery::SORT => 710,
                    T\Delivery::ADDSUM => 24.37,
                ],
            ],
        ],
        'DS_DHL_NO' => [
            T\Deliveryset::TITLE => 'DHL Paket (NO)',
            T\Deliveryset::POS => 800,
            T\Deliveryset::ACTIVE => true,
            'countries' => $no,
            'rules' => [
                'PORTO' => [
                    T\Delivery::TITLE => 'DHL NO -> 29,41 € Porto',
                    T\Delivery::SORT => 810,
                    T\Delivery::ADDSUM => 29.41,
                ],
            ],
        ],
    ], [
        'oxidcashondel' => $payment([
            T\Payments::DESC => 'Nachnahme',
            T\Payments::ADDSUM => 5,
            T\Payments::TOAMOUNT => 2500,
            'countries' => $de
        ]),
        'oxidpayadvance' => $payment([
            T\Payments::DESC => 'Vorkasse',
            T\Payments::CHECKED => true
        ]),
        'oxidinvoice' => $payment([
            T\Payments::DESC => 'Rechnung',
            'groups' => ['oxiddealer'],
        ]),
        'oxempty' => $payment([
            T\Payments::DESC => 'Empty',
            T\Payments::TOAMOUNT => 0
        ]),
        'payppaypalplus' => $payment([
            T\Payments::DESC => 'PayPal Plus',
            T\Payments::TOAMOUNT => 10000
        ]),
        'oxidpaypal' => $payment([
            T\Payments::DESC => 'PayPal',
            T\Payments::TOAMOUNT => 99999
        ]),
        'klarna_pay_now' => $payment([
            T\Payments::DESC => 'Sofort bezahlen'
        ]),
        'klarna_pay_later' => $payment([
            T\Payments::DESC => 'Klarna Rechnung',
            'countries' => $de
        ]),
        'klarna_slice_it' => $payment([
            T\Payments::DESC => 'Klarna Ratenkauf',
            T\Payments::ACTIVE => false,
        ]),
        'PAYMENT_CREDIT_PURCHASE' => $payment([
            T\Payments::DESC => 'Zielkauf',
            T\Payments::FROMAMOUNT => 250,
            'countries' => $de
        ]),
        'PAYMENT_FINANCING' => $payment([
            T\Payments::DESC => 'Finanzierung',
            T\Payments::FROMAMOUNT => 250,
            'countries' => $de
        ]),
    ]);

    yield from $ds($shop, $commit);
};
