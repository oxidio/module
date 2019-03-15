<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Module;

use JsonSerializable;
use OxidEsales\Eshop\Core\Registry;

/**
 */
class Events implements JsonSerializable
{
    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return [
            'onActivate'   => [static::class, 'onActivate'],
            'onDeactivate' => [static::class, 'onDeactivate'],
        ];
    }

    /**
     * @return bool
     */
    public static function onActivate(): bool
    {
        return ($module = self::getCurrentModule()) && $module->activate();
    }

    /**
     * @return bool
     */
    public static function onDeactivate(): bool
    {
        return ($module = self::getCurrentModule()) && $module->deactivate();
    }

    /**
     * @see \OxidEsales\EshopCommunity\Core\Module\ModuleInstaller::_callEvent
     *
     * @return Module|null
     */
    private static function getCurrentModule(): ?Module
    {
        $req = Registry::getRequest();
        $id  = $req->getRequestParameter('oxid') ?: debug_backtrace(FALSE, 4)[3]['args'][1] ?? null;
        return Provider::has($id) ? Provider::module($id) : null;
    }
}
