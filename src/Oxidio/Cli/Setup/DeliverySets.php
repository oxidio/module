<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Cli\Setup;

use php;

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
            foreach ($record as ['countries' => $codes]) {
                foreach ($codes ?? [] as $code) {
                    $countries[$code] = $code;
                }
            }
        }
        return $shop->query(TAB\OXCOUNTRY, static function (Core\Row $row) {
            return $row(COUNTRY\OXISOALPHA2, COUNTRY\OXID);
        }, [COUNTRY\OXISOALPHA2 => ['IN', php\values($countries)]]);
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
                $data = php\traverse($shop->query(TAB\OXCATEGORIES, function(Core\Row $row) {
                    return php\mapKey($row[CAT\OXID])->andValue($row);
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
                yield $arg => $data[$arg][CAT\OXID] ?? $arg;
            }
        };

        $shop->modify(TAB\OXCOUNTRY)->update([COUNTRY\OXACTIVE => false]);
        $shop->modify(TAB\OXCOUNTRY)->update([COUNTRY\OXACTIVE => true], [COUNTRY\OXID => ['IN', $countries]]);

        $shop->modify(TAB\OXPAYMENTS)->update([OXPAYMENTS\OXACTIVE => false]);
        $shop->modify(TAB\OXPAYMENTS)->replace($this->payments, OXPAYMENTS\OXID);
        $o2p = [];
        $o2g = [];
        foreach ($this->payments as $id => ['countries' => $codes, 'groups' => $groups]) {
            foreach ($codes ?? [] as $country) {
                $o2p["{$id}_{$country}"] = [
                    O2P\OXPAYMENTID => $id,
                    O2P\OXOBJECTID => $countries[$country] ?? null,
                    O2P\OXTYPE => TAB\OXCOUNTRY,
                ];
            }
            foreach (php\keys($this->sets) as $setId) {
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
            [O2P\OXPAYMENTID => ['IN', php\keys($this->payments)]],
            [O2P\OXOBJECTID => ['IN', php\keys($this->sets)]]
        );
        $shop->modify(TAB\OXOBJECT2PAYMENT)->replace($o2p, O2P\OXID);

        $shop->modify(TAB\OXOBJECT2GROUP)->delete([O2G\OXOBJECTID => ['IN', php\keys($this->payments)]]);
        $shop->modify(TAB\OXOBJECT2GROUP)->replace($o2g, O2G\OXID);

        $shop->modify(TAB\OXDELIVERYSET)->update([SET\OXACTIVE => false]);
        $shop->modify(TAB\OXDELIVERYSET)->replace($this->sets, SET\OXID);

        $s2c = [];
        $del = [];
        $d2s = [];
        foreach ($this->sets as $setId => ['countries' => $codes, 'rules' => $rules]) {
            foreach ($codes ?? [] as $country) {
                $s2c["{$setId}_{$country}"] = [
                    O2D\OXDELIVERYID => $setId,
                    O2D\OXOBJECTID => $countries[$country] ?? null,
                    O2D\OXTYPE => 'oxdelset',
                ];
            }
            foreach ($rules ?? [] as $suffix => $rule) {
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
                foreach ($categories(...$rule['categories'] ?? []) as $catId) {
                    $s2c[$delId . $catId] = [
                        O2D\OXDELIVERYID => $delId,
                        O2D\OXOBJECTID => $catId,
                        O2D\OXTYPE => TAB\OXCATEGORIES,
                    ];
                }
            }
        }
        $shop->modify(TAB\OXOBJECT2DELIVERY)->delete([O2D\OXDELIVERYID => ['IN', php\keys($this->sets, $del)]]);
        $shop->modify(TAB\OXOBJECT2DELIVERY)->replace($s2c, O2D\OXID);

        $shop->modify(TAB\OXDELIVERY)->update([DEL\OXACTIVE => false]);
        $shop->modify(TAB\OXDELIVERY)->replace($del, DEL\OXID);

        $shop->modify(TAB\OXDEL2DELSET)->delete([D2S\OXDELSETID => ['IN', php\keys($this->sets)]]);
        $shop->modify(TAB\OXDEL2DELSET)->replace($d2s, D2S\OXID);

        foreach ($shop->commit($commit) as $result) {
            yield php\io((object)$result, php\Cli\IO::VERBOSITY_VERBOSE);
        }
    }
}
