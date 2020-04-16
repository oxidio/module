<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace PHPSTORM_META {
    override(\oxNew(0), type(0));
    override(\Oxidio::di(0), type(0));
    override(\Oxidio::call(0), type(0));
    override(\Oxidio\Oxidio::di(0), type(0));
    override(\Oxidio\Oxidio::call(0), type(0));
    override(\OxidEsales\EshopCommunity\Core\UtilsObject::oxNew(0), type(0));
    override(\OxidEsales\EshopCommunity\Core\Registry::get(0), type(0));
//    override(\OxidEsales\EshopCommunity\Setup\Core::getInstance(0), type(0));
    override(\OxidEsales\EshopCommunity\Setup\Core::getInstance(0), map([
        '' => '\OxidEsales\EshopCommunity\Setup\@',
    ]));
}
