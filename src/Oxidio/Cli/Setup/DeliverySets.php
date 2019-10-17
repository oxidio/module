<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Cli\Setup;

use php;

use Generator;
use Oxidio\Enum\Tables as T;
use Oxidio\Core;

/**
 * @property-read array[] $sets
 * @property-read array[] $payments
 */
class DeliverySets
{
    use php\PropertiesTrait\ReadOnly;

    /**
     * @param array $sets
     * @param array $payments
     */
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
        foreach ([$this->sets, $this->payments] as $record) {
            foreach ($record['countries'] ?? [] as $codes) {
                foreach ($codes ?? [] as $code) {
                    $countries[$code] = $code;
                }
            }
        }
        return $shop->query(T::COUNTRY, static function (Core\Row $row) {
            return $row(T\Country::ISOALPHA2, T\Country::ID);
        }, [T\Country::ISOALPHA2 => ['IN', php\values($countries)]]);
    }

    /**
     * @param Core\Shop $shop
     * @param bool $commit
     * @return Generator
     */
    public function __invoke(Core\Shop $shop, bool $commit = false): Generator
    {
        $countries = $this->countries($shop);

        $categories = function (...$args) use ($shop) {
            static $data;
            if ($data === null) {
                $data = php\traverse($shop->query(T::CATEGORIES, function(Core\Row $row) {
                    return php\mapKey($row[T\Categories::ID])->andValue($row);
                }));
                foreach ($data as $row) {
                    $parent = $row;
                    $path = [];
                    do {
                        $path[] = strtolower($shop::seo($parent[T\Categories::TITLE]));
                    } while ($parent = $data[$parent[T\Categories::PARENTID]] ?? null);
                    $data[implode('/', array_reverse($path))] = $row;
                }
            }
            foreach ($args as $arg) {
                yield $arg => $data[$arg][T\Categories::ID] ?? $arg;
            }
        };

        $shop->modify(T::COUNTRY)->update([T\Country::ACTIVE => false]);
        $shop->modify(T::COUNTRY)->update([T\Country::ACTIVE => true], [T\Country::ID => ['IN', $countries]]);

        $shop->modify(T::PAYMENTS)->update([T\Payments::ACTIVE => false]);
        $shop->modify(T::PAYMENTS)->replace($this->payments, T\Payments::ID);
        $o2p = [];
        $o2g = [];
        foreach ($this->payments as $id => $payment) {
            foreach ($payment['countries'] ?? [] as $country) {
                $o2p["{$id}_{$country}"] = [
                    T\Object2payment::PAYMENTID => $id,
                    T\Object2payment::OBJECTID => $countries[$country] ?? null,
                    T\Object2payment::TYPE => T::COUNTRY,
                ];
            }
            foreach (php\keys($this->sets) as $setId) {
                $o2p["{$id}_{$setId}"] = [
                    T\Object2payment::PAYMENTID => $id,
                    T\Object2payment::OBJECTID => $setId,
                    T\Object2payment::TYPE => 'oxdelset',
                ];
            }

            foreach ($payment['groups'] ?? [] as $group) {
                $o2g["{$id}_{$group}"] = [
                    T\Object2group::OBJECTID => $id,
                    T\Object2group::GROUPSID => $group,
                ];
            }
        }
        $shop->modify(T::OBJECT2PAYMENT)->delete(
            [T\Object2payment::PAYMENTID => ['IN', php\keys($this->payments)]],
            [T\Object2payment::OBJECTID => ['IN', php\keys($this->sets)]]
        );
        $shop->modify(T::OBJECT2PAYMENT)->replace($o2p, T\Object2payment::ID);

        $shop->modify(T::OBJECT2GROUP)->delete([T\Object2group::OBJECTID => ['IN', php\keys($this->payments)]]);
        $shop->modify(T::OBJECT2GROUP)->replace($o2g, T\Object2group::ID);

        $shop->modify(T::DELIVERYSET)->update([T\Deliveryset::ACTIVE => false]);
        $shop->modify(T::DELIVERYSET)->replace($this->sets, T\Deliveryset::ID);

        $s2c = [];
        $del = [];
        $d2s = [];
        foreach ($this->sets as $setId => $set) {
            foreach ($set['countries'] ?? [] as $country) {
                $s2c["{$setId}_{$country}"] = [
                    T\Object2delivery::DELIVERYID => $setId,
                    T\Object2delivery::OBJECTID => $countries[$country] ?? null,
                    T\Object2delivery::TYPE => 'oxdelset',
                ];
            }
            foreach ($set['rules'] ?? [] as $suffix => $rule) {
                $del[$delId = "{$setId}:{$suffix}"] = $rule + [
                        T\Delivery::ACTIVE => true,
                        T\Delivery::FINALIZE => true,
                        T\Delivery::ADDSUMTYPE => 'abs',
                        T\Delivery::DELTYPE => 'p',
                        T\Delivery::PARAM => 0,
                        T\Delivery::PARAMEND => 1000000,
                    ];
                $d2s[$delId] = [
                    T\Del2delset::DELID => $delId,
                    T\Del2delset::DELSETID => $setId,
                ];
                foreach ($categories(...$rule['categories'] ?? []) as $catId) {
                    $s2c[$delId . $catId] = [
                        T\Object2delivery::DELIVERYID => $delId,
                        T\Object2delivery::OBJECTID => $catId,
                        T\Object2delivery::TYPE => T::CATEGORIES,
                    ];
                }
            }
        }
        $shop->modify(T::OBJECT2DELIVERY)->delete([T\Object2delivery::DELIVERYID => ['IN', php\keys($this->sets, $del)]]);
        $shop->modify(T::OBJECT2DELIVERY)->replace($s2c, T\Object2delivery::ID);

        $shop->modify(T::DELIVERY)->update([T\Delivery::ACTIVE => false]);
        $shop->modify(T::DELIVERY)->replace($del, T\Delivery::ID);

        $shop->modify(T::DEL2DELSET)->delete([T\Del2delset::DELSETID => ['IN', php\keys($this->sets)]]);
        $shop->modify(T::DEL2DELSET)->replace($d2s, T\Del2delset::ID);

        foreach ($shop->commit($commit) as $result) {
            yield php\io((object)$result, php\Cli\IO::VERBOSITY_VERBOSE);
        }
    }
}
