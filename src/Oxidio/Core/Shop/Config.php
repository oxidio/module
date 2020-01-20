<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core\Shop;

use Generator;
use Oxidio\Core;
use Oxidio\Enum\Tables as T;
use SebastianBergmann\Diff\{Differ, Output\UnifiedDiffOutputBuilder};
use Php;

/**
 * @property-read iterable $types
 * @property-read iterable $values
 */
class Config
{
    use Php\PropertiesTrait\ReadOnly;

    public const T_ARR = 'arr';
    public const T_AARR = 'aarr';
    public const T_BOOL = 'bool';
    public const T_PASSWORD = 'password';
    public const T_SELECT = 'select';
    public const T_STR = 'str';
    public const INITIAL = [
        'aCacheViews' => ['start', 'alist', 'details'],
        'aCMSfolder' => ['CMSFOLDER_EMAILS' => '#706090', 'CMSFOLDER_USERINFO' => '#303030', 'CMSFOLDER_PRODUCTINFO' => '#303030', 'CMSFOLDER_NONE' => '#904040'],
        'aCurrencies' => ['EUR@ 1.00@ ,@ .@ €@ 2', 'GBP@ 0.8565@ .@  @ £@ 2', 'CHF@ 1.4326@ ,@ .@ <small>CHF</small>@ 2', 'USD@ 1.2994@ .@  @ $@ 2'],
        'aDetailImageSizes' => ['oxpic1' => '250*200', 'oxpic2' => '250*200', 'oxpic3' => '250*200', 'oxpic4' => '250*200', 'oxpic5' => '250*200', 'oxpic6' => '250*200', 'oxpic7' => '250*200', 'oxpic8' => '250*200', 'oxpic9' => '250*200', 'oxpic10' => '250*200', 'oxpic11' => '250*200', 'oxpic12' => '250*200'],
        'aHomeCountry' => ['a7c40f631fc920687.20179984'],
        'aInterfaceProfiles' => ['Standard' => '10', '1024x768' => '10', '1280x1024' => '17', '1600x1200' => '22'],
        'aLanguageParams' => ['de' => ['baseId' => 0, 'active' => '1', 'sort' => '1'], 'en' => ['baseId' => 1, 'active' => '1', 'sort' => '2']],
        'aLanguages' => ['de' => 'Deutsch', 'en' => 'English'],
        'aLanguageSSLURLs' => ['', NULL],
        'aLanguageURLs' => ['', NULL],
        'aLogSkipTags' => [],
        'aMustFillFields' => ['oxuser__oxfname', 'oxuser__oxlname', 'oxuser__oxstreet', 'oxuser__oxstreetnr', 'oxuser__oxzip', 'oxuser__oxcity', 'oxuser__oxcountryid', 'oxaddress__oxfname', 'oxaddress__oxlname', 'oxaddress__oxstreet', 'oxaddress__oxstreetnr', 'oxaddress__oxzip', 'oxaddress__oxcity', 'oxaddress__oxcountryid'],
        'aNrofCatArticles' => ['10', '20', '50', '100'],
        'aNrofCatArticlesInGrid' => ['12', '16', '24', '32'],
        'aOrderfolder' => ['ORDERFOLDER_NEW' => '#0000FF', 'ORDERFOLDER_FINISHED' => '#0A9E18', 'ORDERFOLDER_PROBLEMS' => '#FF0000'],
        'aSearchCols' => ['oxtitle', 'oxshortdesc', 'oxsearchkeys', 'oxartnum'],
        'aSEOReservedWords' => ['admin', 'core', 'export', 'modules', 'out', 'setup', 'views'],
        'aSkipTags' => ['der', 'die', 'das', 'was', 'wie', 'wer', 'in', 'sie', 'du', 'aus', 'von', 'des', 'hat', 'einen', 'eine', 'ist', 'einem', 'dann', 'haben', 'dieser', 'dieser', 'dem', 'sich', 'er', 'ich', 'was', 'fÜr', 'und', 'nur', 'auf', 'an', 'this', 'that', 'if', 'you', 'the'],
        'aSortCols' => ['oxtitle', 'oxvarminprice'],
        'blAllowNegativeStock' => '',
        'blAllowUnevenAmounts' => '',
        'blAutoIcons' => '1',
        'blCalculateDelCostIfNotLoggedIn' => '',
        'blCheckSysReq' => '1',
        'blCheckTemplates' => '1',
        'blConfirmAGB' => '',
        'blDisableDublArtOnCopy' => '1',
        'blDisableNavBars' => '1',
        'blDontShowEmptyCategories' => '',
        'blEnableIntangibleProdAgreement' => '1',
        'blEnableSeoCache' => '1',
        'blEnterNetPrice' => '',
        'blLoadVariants' => '1',
        'blLogChangesInAdmin' => '',
        'blNewArtByInsert' => '1',
        'blOrderOptInEmail' => '1',
        'blOtherCountryOrder' => '1',
        'blSearchUseAND' => '',
        'blSendTechnicalInformationToOxid' => '',
        'blShippingCountryVat' => '',
        'blShowBirthdayFields' => '1',
        'blShowListDisplayType' => '1',
        'blShowRememberMe' => '1',
        'blShowSorting' => '1',
        'blShowTSCODMessage' => '1',
        'blShowTSInternationalFeesMessage' => '1',
        'blStockOffDefaultMessage' => '1',
        'blStockOnDefaultMessage' => '1',
        'blStoreCreditCardInfo' => '',
        'blStoreIPs' => '',
        'blUseMultidimensionVariants' => '1',
        'blUseStock' => '1',
        'blVariantsSelection' => '',
        'blWarnOnSameArtNums' => '1',
        'blWrappingVatOnTop' => '',
        'bl_perfCalcVatOnlyForBasketOrder' => '',
        'bl_perfLoadAccessoires' => '1',
        'bl_perfLoadAktion' => '1',
        'bl_perfLoadCatTree' => '1',
        'bl_perfLoadCrossselling' => '1',
        'bl_perfLoadCurrency' => '1',
        'bl_perfLoadCustomerWhoBoughtThis' => '1',
        'bl_perfLoadDelivery' => '1',
        'bl_perfLoadDiscounts' => '1',
        'bl_perfLoadLanguages' => '1',
        'bl_perfLoadManufacturerTree' => '1',
        'bl_perfLoadNews' => '1',
        'bl_perfLoadNewsOnlyStart' => '1',
        'bl_perfLoadPrice' => '1',
        'bl_perfLoadPriceForAddList' => '1',
        'bl_perfLoadReviews' => '1',
        'bl_perfLoadSelectLists' => '1',
        'bl_perfLoadSimilar' => '1',
        'bl_perfLoadTreeForSearch' => '1',
        'bl_perfParseLongDescinSmarty' => '1',
        'bl_perfShowActionCatArticleCnt' => '1',
        'bl_perfUseSelectlistPrice' => '',
        'bl_rssCategories' => '1',
        'bl_rssNewest' => '1',
        'bl_rssSearch' => '1',
        'bl_rssTopShop' => '1',
        'bl_showCompareList' => '1',
        'bl_showGiftWrapping' => '1',
        'bl_showListmania' => '1',
        'bl_showVouchers' => '1',
        'bl_showWishlist' => '1',
        'dDefaultVAT' => '19',
        'dPointsForInvitation' => '10',
        'dPointsForRegistration' => '10',
        'iAttributesPercent' => '70',
        'iCntofMails' => '20',
        'iDownloadExpirationTime' => '24',
        'iExportNrofLines' => '250',
        'iLinkExpirationTime' => '168',
        'iMallMode' => '1',
        'iMaxDownloadsCount' => '0',
        'iMaxDownloadsCountUnregistered' => '1',
        'includeProductReviewLinksInEmail' => '',
        'iNewBasketItemMessage' => '1',
        'iNewestArticlesMode' => '1',
        'iNrofCrossellArticles' => '5',
        'iNrofCustomerWhoArticles' => '5',
        'iNrofNewcomerArticles' => '4',
        'iNrofSimilarArticles' => '5',
        'iRssItemsCount' => '20',
        'iSessionTimeout' => '60',
        'iTop5Mode' => '1',
        'iTopNaviCatCount' => '4',
        'iUseGDVersion' => '2',
        'sAdditionalServVATCalcMethod' => 'biggest_net',
        'sCatIconsize' => '168*100',
        'sCatPromotionsize' => '370*107',
        'sCatThumbnailsize' => '555*200',
        'sCntOfNewsLoaded' => '1',
        'sCSVSign' => ';',
        'sDefaultImageQuality' => '75',
        'sDefaultLang' => '0',
        'sDefaultListDisplayType' => 'infogrid',
        'sDownloadsDir' => 'out/downloads',
        'sGiCsvFieldEncloser' => '"',
        'sGZSLogFile' => '',
        'sHost' => 'https://txms.gzs.de:51384/',
        'sIconsize' => '56*42',
        'sLargeCustPrice' => '100',
        'sMerchantID' => '',
        'sMidlleCustPrice' => '40',
        'sParcelService' => 'http://www.dpd.de/cgi-bin/delistrack?typ=1&amp;lang=de&amp;pknr=##ID##',
        'sPaymentPwd' => '',
        'sPaymentUser' => '',
        'sStockWarningLimit' => '10',
        'sTagList' => '1153227019',
        'sTheme' => 'flow',
        'sThumbnailsize' => '100*100',
        'sUtilModule' => '',
        'sZoomImageSize' => '450*450',
    ];

    private const DIFF = [1 => '+ ', 2 => '- '];

    public function __construct(iterable $modules = [Core\Extension::SHOP => self::INITIAL])
    {
        $this->properties = ['types' => [], 'values' => []];
        foreach ($modules as $module => $values) {
            foreach ($values as $key => $value) {
                $parts =  explode(':', $key);
                $key = $parts[0];
                $this->properties['types'][$module][$key] = $parts[1] ?? self::assumeType($value, $key);
                $this->properties['values'][$module][$key] = $value;
            }
        }
    }

    public function diff(iterable $modules): Generator
    {
        $differ = new Differ(new UnifiedDiffOutputBuilder('', false));
        foreach ($modules as $module => $config) {
            foreach ($config as $name => $value) {
                $converted = self::convert($value);
                $diff = false;
                if (isset($this->values[$module][$name])) {
                    $lines = [];
                    $count = 0;
                    foreach ($differ->diffToArray(self::convert($this->values[$module][$name]), $converted) as $line) {
                        $count += $line[1];
                        $lines[] = (self::DIFF[$line[1]] ?? '  ') . $line[0];
                    }
                    $diff = implode(is_array($value) ? '' : PHP_EOL, $count ? $lines : []);
                }
                yield $name => [$converted,  $diff, $module];
            }
        }
    }

    private static function convert($value)
    {
        return is_string($value) ? $value : json_encode(is_bool($value) ? (int)$value : $value, JSON_PRETTY_PRINT);
    }

    private static function id($id): string
    {
        $id = explode(':', $id);
        return strtolower($id[1] ?? $id[0]);
    }

    public function modules(Core\Shop $shop): array
    {
        $ids = Php::arr(Php::keys($this->values), function ($id) {
            yield [self::id($id)] => $id;
        });
        return Php::arr([Core\Extension::SHOP => $shop], $shop->modules, function ($ext, $id) use ($ids) {
            yield [$ids[self::id($id)] ?? $id] => $ext->config;
        });
    }

    /**
     * @param Core\Shop $shop
     * @return Generator
     */
    public function __invoke(Core\Shop $shop): Generator
    {
        yield T::CONFIG => function (Core\Shop $shop) {
            foreach ($this->values as $module => $config) {
                foreach ($config as $key => $value) {
                    yield [T\CONFIG::MODULE => $module, T\CONFIG::VARNAME => $key] => [
                        T\CONFIG::ID => $shop::id($module, $key),
                        T\CONFIG::VARTYPE => $this->types[$module][$key],
                        T\CONFIG::VARVALUE => static function ($column) use ($value, $shop) {
                            return ["ENCODE(:$column, '{$shop->configKey}')" => is_array($value) ? serialize($value) : $value];
                        },
                    ];
                }
            }
        };
    }

    private static function assumeType($value, $key): string
    {
        if (is_array($value)) {
            return is_array(current($value)) ? self::T_AARR : self::T_ARR;
        }
        if (strpos($key, 'bl') === 0) {
            return self::T_BOOL;
        }
        return self::T_STR;
    }
}
