<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Setup\VARS
{
    const EULA              = 'iEula';
    const DB                = 'aDB';
    const PATH              = 'aPath';
    const CONFIG            = 'aSetupConfig';
    const DATA              = 'aAdminData';
    const OVERWRITE         = 'ow'; // blOverwrite
    const IGNORE            = 'owrec'; // blIgnoreDbRecommendations
    const LANG_SETUP        = 'setup_lang';
    const LANG_SETUP_SUBMIT = 'setup_lang_submit';
    const SID               = 'sid';
    const COUNTRY           = 'country_lang';
    const LANG              = 'sShopLang';
    const SEND              = 'send_technical_information_to_oxid';
    const UPDATES           = 'check_for_updates';
    const STEP              = 'istep';
}

namespace OxidEsales\EshopCommunity\Setup
{
    use OxidEsales\EshopCommunity\Setup\VARS;

    const VARS = [
        VARS\EULA              => 'post',
        VARS\DB                => 'post',
        VARS\EULA              => 'post',
        VARS\CONFIG            => 'post',
        VARS\DATA              => 'post',
        VARS\OVERWRITE         => 'get',
        VARS\IGNORE            => 'get',
        VARS\LANG_SETUP        => 'post',
        VARS\LANG_SETUP_SUBMIT => 'post',
        VARS\SID               => 'get',
        VARS\COUNTRY           => 'post',
        VARS\LANG              => 'post',
        VARS\SEND              => 'post',
        VARS\UPDATES           => 'post',
        VARS\STEP              => 'post',
    ];
}
