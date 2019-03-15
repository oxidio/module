<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Module;

use JsonSerializable;
use OxidEsales\Eshop\Core\Module\Module as OxidModule;

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
        return self::activate(true);
    }

    /**
     * @return bool
     */
    public static function onDeactivate(): bool
    {
        return self::activate(false);
    }

    /**
     * @see \OxidEsales\EshopCommunity\Core\Module\ModuleInstaller::activate
     * @see \OxidEsales\EshopCommunity\Core\Module\ModuleInstaller::deactivate
     * @see \OxidEsales\EshopCommunity\Core\Module\ModuleInstaller::_callEvent
     *
     * @param bool $enable
     *
     * @return bool
     */
    private static function activate(bool $enable): bool
    {
        $module = debug_backtrace(FALSE, 5)[4]['args'][0] ?? null;
        if ($module instanceof OxidModule && Provider::has($id = $module->getId())) {
            return Provider::module($id)->activate($enable, $module);
        }
        return false;
    }
}
