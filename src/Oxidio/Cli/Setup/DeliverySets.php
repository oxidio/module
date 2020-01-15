<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Cli\Setup;

use Oxidio\Oxidio;
use Php;
use Generator;
use Oxidio\Enum\Tables as T;
use Oxidio\Core;

/**
 * @property-read array[] $sets
 * @property-read array[] $payments
 */
class DeliverySets
{
    use Php\PropertiesTrait\ReadOnly;

    public function __construct(array $sets, array $payments)
    {
        $this->properties = [
            'sets' => $sets,
            'payments' => $payments
        ];
    }

    private function countries(Core\Shop $shop): Core\DataQuery
    {
        $countries = [];
        foreach ($this->sets + $this->payments as $record) {
            foreach ($record['countries'] ?? [] as $code) {
                $countries[$code] = $code;
            }
        }
        return $shop->query(T::COUNTRY, static function (Core\Row $row) {
            return $row(T\COUNTRY::ISOALPHA2, T\COUNTRY::ID);
        }, [T\COUNTRY::ISOALPHA2 => ['IN', Php::values($countries)]]);
    }

    public function __invoke(Core\Shop $shop, bool $commit = false): Generator
    {
        $countries = $this->countries($shop);

        $categories = function (...$args) use ($shop) {
            static $data;
            if ($data === null) {
                $data = Php::traverse($shop->query(T::CATEGORIES, function (Core\Row $row) {
                    return Php::mapKey($row[T\CATEGORIES::ID])->andValue($row);
                }));
                foreach ($data as $row) {
                    $parent = $row;
                    $path = [];
                    do {
                        $path[] = strtolower(Oxidio::seo($parent[T\CATEGORIES::TITLE]));
                    } while ($parent = $data[$parent[T\CATEGORIES::PARENTID]] ?? null);
                    $data[implode('/', array_reverse($path))] = $row;
                }
            }
            foreach ($args as $arg) {
                yield $arg => $data[$arg][T\CATEGORIES::ID] ?? $arg;
            }
        };

        $shop->modify(T::COUNTRY)->update([T\COUNTRY::ACTIVE => false]);
        $shop->modify(T::COUNTRY)->update([T\COUNTRY::ACTIVE => true], [T\COUNTRY::ID => ['IN', $countries]]);

        $shop->modify(T::PAYMENTS)->update([T\PAYMENTS::ACTIVE => false]);
        $shop->modify(T::PAYMENTS)->replace($this->payments, T\PAYMENTS::ID);
        $o2p = [];
        $o2g = [];
        foreach ($this->payments as $id => $payment) {
            foreach ($payment['countries'] ?? [] as $country) {
                $o2p["{$id}_{$country}"] = [
                    T\O2PAYMENT::PAYMENTID => $id,
                    T\O2PAYMENT::OBJECTID => $countries[$country] ?? null,
                    T\O2PAYMENT::TYPE => T::COUNTRY,
                ];
            }
            foreach (Php::keys($this->sets) as $setId) {
                $o2p["{$id}_{$setId}"] = [
                    T\O2PAYMENT::PAYMENTID => $id,
                    T\O2PAYMENT::OBJECTID => $setId,
                    T\O2PAYMENT::TYPE => 'oxdelset',
                ];
            }

            foreach ($payment['groups'] ?? [] as $group) {
                $o2g["{$id}_{$group}"] = [
                    T\O2GROUP::OBJECTID => $id,
                    T\O2GROUP::GROUPSID => $group,
                ];
            }
        }
        $shop->modify(T::O2PAYMENT)->delete(
            [T\O2PAYMENT::PAYMENTID => ['IN', Php::keys($this->payments)]],
            [T\O2PAYMENT::OBJECTID => ['IN', Php::keys($this->sets)]]
        );
        $shop->modify(T::O2PAYMENT)->replace($o2p, T\O2PAYMENT::ID);

        $shop->modify(T::O2GROUP)->delete([T\O2GROUP::OBJECTID => ['IN', Php::keys($this->payments)]]);
        $shop->modify(T::O2GROUP)->replace($o2g, T\O2GROUP::ID);

        $shop->modify(T::DELIVERYSET)->update([T\DELIVERYSET::ACTIVE => false]);
        $shop->modify(T::DELIVERYSET)->replace($this->sets, T\DELIVERYSET::ID);

        $s2c = [];
        $del = [];
        $d2s = [];
        foreach ($this->sets as $setId => $set) {
            foreach ($set['countries'] ?? [] as $country) {
                $s2c["{$setId}_{$country}"] = [
                    T\O2DELIVERY::DELIVERYID => $setId,
                    T\O2DELIVERY::OBJECTID => $countries[$country] ?? null,
                    T\O2DELIVERY::TYPE => 'oxdelset',
                ];
            }
            foreach ($set['rules'] ?? [] as $suffix => $rule) {
                $del[$delId = "{$setId}:{$suffix}"] = $rule + [
                        T\DELIVERY::ACTIVE => true,
                        T\DELIVERY::FINALIZE => true,
                        T\DELIVERY::ADDSUMTYPE => 'abs',
                        T\DELIVERY::DELTYPE => 'p',
                        T\DELIVERY::PARAM => 0,
                        T\DELIVERY::PARAMEND => 1000000,
                    ];
                $d2s[$delId] = [
                    T\DEL2DELSET::DELID => $delId,
                    T\DEL2DELSET::DELSETID => $setId,
                ];
                foreach ($categories(...$rule['categories'] ?? []) as $catId) {
                    $s2c[$delId . $catId] = [
                        T\O2DELIVERY::DELIVERYID => $delId,
                        T\O2DELIVERY::OBJECTID => $catId,
                        T\O2DELIVERY::TYPE => T::CATEGORIES,
                    ];
                }
            }
        }
        $shop->modify(T::O2DELIVERY)->delete([T\O2DELIVERY::DELIVERYID => ['IN', Php::keys($this->sets, $del)]]);
        $shop->modify(T::O2DELIVERY)->replace($s2c, T\O2DELIVERY::ID);

        $shop->modify(T::DELIVERY)->update([T\DELIVERY::ACTIVE => false]);
        $shop->modify(T::DELIVERY)->replace($del, T\DELIVERY::ID);

        $shop->modify(T::DEL2DELSET)->delete([T\DEL2DELSET::DELSETID => ['IN', Php::keys($this->sets)]]);
        $shop->modify(T::DEL2DELSET)->replace($d2s, T\DEL2DELSET::ID);

        foreach ($shop->commit($commit) as $result) {
            yield new Php\Cli\Renderable((object)$result, Php\Cli\IO::VERBOSITY_VERBOSE);
        }
    }
}
