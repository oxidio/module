<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Module
{
    const ID          = 'id';
    const TITLE       = 'title';
    const DESCRIPTION = 'description';
    const URL         = 'url';
    const VERSION     = 'version';
    const AUTHOR      = 'author';
    const EMAIL       = 'email';
    const SETTINGS    = 'settings';
    const BLOCKS      = 'blocks';
    const EXTEND      = 'extend';
    const CLI         = 'cli';
    const MENU        = 'menu';
}

namespace Oxidio\Module\Settings
{
    const TYPE     = 'type';
    const NAME     = 'name';
    const GROUP    = 'group';
    const VALUE    = 'value';
    const LABEL    = 'label';
    const SELECTED = 'selected';
    const HELP     = '?';
}

namespace Oxidio\Module\Menu
{
    /**
     * ESHOP ADMIN
     */
    const ADMIN = 'NAVIGATION_ESHOPADMIN';
}

namespace Oxidio\Module\Menu\ADMIN
{
    use const Oxidio\Module\Menu\ADMIN as _;

    /**
     * Master Settings
     */
    const MAIN       = _ . '/mxmainmenu';

    /**
     * Shop Settings
     */
    const SETTINGS   = _ . '/mxshopsett';

    /**
     * Extensions
     */
    const EXTENSIONS = _ . '/mxextensions';

    /**
     * Administer Products
     */
    const PRODUCTS   = _ . '/mxmanageprod';

    /**
     * Administer Users
     */
    const USERS      = _ . '/mxuadmin';

    /**
     * Administer Orders
     */
    const ORDERS     = _ . '/mxorders';

    /**
     * Customer Info
     */
    const INFO       = _ . '/mxcustnews';

    /**
     * Service
     */
    const SERVICE    = _ . '/mxservice';
}

namespace Oxidio\Module\Menu\ADMIN\MAIN
{
    use const Oxidio\Module\Menu\ADMIN\MAIN AS _;

    /**
     * Core Settings
     */
    const CORE = _ . '/mxcoresett';

    /**
     * Countries
     */
    const COUNTRIES = _ . '/mxcountries';

    /**
     * Distributors
     */
    const VENDORS = _ . '/mxvendor';

    /**
     * Brands/Manufacturers
     */
    const MANUFACTURERS = _ . '/mxmanufacturer';

    /**
     * Languages
     */
    const LANGUAGES = _ . '/mxlanguages';
}

namespace Oxidio\Module\Menu\ADMIN\SETTINGS
{
    use const Oxidio\Module\Menu\ADMIN\SETTINGS AS _;

    /**
     * Payment Methods
     */
    const PAYMENTS = _ . '/mxpaymeth';

    /**
     * Discounts
     */
    const DISCOUNTS = _ . '/mxdiscount';

    /**
     * Shipping Methods
     */
    const SHIPPING = _ . '/mxshippingset';

    /**
     * Shipping Cost Rules
     */
    const COST_RULES = _ . '/mxshipping';

    /**
     * Coupon Series
     */
    const VOUCHERS = _ . '/mxvouchers';

    /**
     * Gift Wrapping
     */
    const WRAPPINGS = _ . '/mxwrapping';
}

namespace Oxidio\Module\Menu\ADMIN\EXTENSIONS
{
    use const Oxidio\Module\Menu\ADMIN\EXTENSIONS AS _;

    /**
     * Themes
     */
    const THEMES = _ . '/mxtheme';

    /**
     * Modules
     */
    const MODULES = _ . '/mxmodule';
}

namespace Oxidio\Module\Menu\ADMIN\PRODUCTS
{
    use const Oxidio\Module\Menu\ADMIN\PRODUCTS AS _;

    /**
     * Products
     */
    const ARTICLES = _ . '/mxarticles';

    /**
     * Attributes
     */
    const ATTRIBUTES = _ . '/mxattributes';

    /**
     * Categories
     */
    const CATEGORIES = _ . '/mxcategories';

    /**
     * Selection Lists
     */
    const LISTS = _ . '/mxsellist';

    /**
     * List All Reviews
     */
    const REVIEWS = _ . '/mxremlist';
}

namespace Oxidio\Module\Menu\ADMIN\USERS
{
    use const Oxidio\Module\Menu\ADMIN\USERS AS _;

    /**
     * Users
     */
    const USERS = _ . '/mxusers';

    /**
     * User Groups
     */
    const GROUPS = _ . '/mxugroups';

    /**
     * List All Users
     */
    const LIST_ALL = _ . '/mxlist';
}

namespace Oxidio\Module\Menu\ADMIN\ORDERS
{
    use const Oxidio\Module\Menu\ADMIN\ORDERS AS _;

    /**
     * Orders
     */
    const ORDERS = _ . '/mxdisplayorders';
}

namespace Oxidio\Module\Menu\ADMIN\INFO
{
    use const Oxidio\Module\Menu\ADMIN\INFO AS _;

    /**
     * News
     */
    const NEWS = _ . '/mxnews';

    /**
     * Newsletter
     */
    const NEWSLETTERS = _ . '/mxnewsletter';

    /**
     * Links
     */
    const LINKS = _ . '/mxurls';

    /**
     * CMS Pages
     */
    const PAGES = _ . '/mxcontent';

    /**
     * Promotions
     */
    const ACTIONS = _ . '/mxactions';

    /**
     * Price Alert
     */
    const PRICE_ALERTS = _ . '/mxpricealarm';
}

namespace Oxidio\Module\Menu\ADMIN\SERVICE
{
    use const Oxidio\Module\Menu\ADMIN\SERVICE AS _;

    /**
     * System Info
     */
    const INFO = _ . '/mxsysinfo';

    /**
     * System health
     */
    const HEALTH = _ . '/mxsysreq';

    /**
     * Diagnostics tool
     */
    const DIAGNOSTICS = _ . '/oxdiag_menu';

    /**
     * Tools
     */
    const TOOLS = _ . '/mxtools';

    /**
     * Product Export
     */
    const EXPORT = _ . '/mxgenexp';

    /**
     * Generic Import
     */
    const IMPORT  = _ . '/mxgenimp';
}
