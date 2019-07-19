<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Cli;
use fn;
use Generator;
use OxidEsales\Eshop\Core\Database\TABLE;
use Oxidio\Core\Shop;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;


class ShopConfig
{
    private const DIFF = [1 => '+ ', 2 => '- '];

    /**
     * Show/modify shop configuration
     *
     * @param fn\Cli\IO $io
     * @param Shop $shop
     * @param bool $clean
     */
    public function __invoke(fn\Cli\IO $io, Shop $shop, bool $clean) {

        $differ = new Differ( new UnifiedDiffOutputBuilder('', false));
        $table = fn\traverse($shop->config, static function ($value, $name) use ($differ) {
            $converted = self::convert($value);

            $lines = [];
            if (fn\hasKey($name, self::INITIAL)) {
                $diff = 0;
                foreach ($differ->diffToArray(self::convert(self::INITIAL[$name]), $converted) as $line) {
                    $diff += $line[1];
                    $lines[] = (self::DIFF[$line[1]] ?? '  ') . $line[0];
                }
                if ($diff) {
                    $name = "<error>$name</error>";
                } else {
                    $name = "<info>$name</info>";
                    $lines = [];
                }
            }
            return [
                'entry' => $name,
                'value' => $converted,
                'diff'  => implode(is_array($value) ? '' : PHP_EOL, $lines),
            ];
        });
        fn\io($table)->toCli($io);

        $io->isVeryVerbose() && fn\io(fn\map($shop->config, static function ($value, $name) {
            return "'$name' => " . (is_array($value) ? new fn\ArrayExport($value) : var_export($value, true)) . ',';
        })->string)->toCli($io);

        $clean && $shop(self::clean());
        foreach ($shop->commit() as $item) {
            $io->isVerbose() && fn\io((object)$item)->toCli($io);
        }
    }

    private static function clean(): Generator
    {
        yield TABLE\OXTPLBLOCKS => null;
        yield TABLE\OXCONFIGDISPLAY => null;
        yield TABLE\OXCONFIG => null;
        yield TABLE\OXCONFIG => static function (Shop $shop) {
            foreach (self::INITIAL as $key => $value) {
                yield $shop::id($key) => [
                    TABLE\OXCONFIG\OXVARNAME => $key,
                    TABLE\OXCONFIG\OXVARTYPE => self::assumeType($value, $key),
                    TABLE\OXCONFIG\OXVARVALUE => static function ($column) use ($value, $shop) {
                        return ["ENCODE(:$column, '{$shop->configKey}')" => is_array($value) ? serialize($value) : $value];
                    },
                ];
            }
        };
    }

    private static function assumeType($value, $key): string
    {
        if (is_array($value)) {
            return is_array(current($value)) ? 'aarr' : 'arr';
        }
        if (strpos($key, 'bl') === 0) {
            return 'bool';
        }
        return 'str';
    }

    private static function convert($value)
    {
        return is_string($value) ? $value : json_encode($value, JSON_PRETTY_PRINT);
    }

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
}
