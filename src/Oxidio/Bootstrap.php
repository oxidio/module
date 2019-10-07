<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio;

use BootstrapConfigFileReader;
use Closure;
use Dotenv\Dotenv;
use OxidEsales\Eshop\Core\ConfigFile as ShopConfigFile;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\Facts\Config\ConfigFile as FactsConfigFile;

class Bootstrap
{
    /**
     * @param Config|BootstrapConfigFileReader|FactsConfigFile|ShopConfigFile $config
     */
    public static function bootstrap($config): void
    {
        if (class_exists(Dotenv::class)) {
            Dotenv::create(INSTALLATION_ROOT_PATH)->overload();
        }

        Closure::bind(function () {
            $this->sShopDir    = OX_BASE_PATH;
            $this->sCompileDir = OX_BASE_PATH . 'tmp/';
            $this->dbHost      = getenv('DB_HOST') ?: 'localhost';
            $this->dbPort      = getenv('DB_PORT') ?: 3306;
            $this->dbName      = getenv('DB_NAME') ?: 'project';
            $this->dbUser      = getenv('DB_USER') ?: 'root';
            $this->dbPwd       = getenv('DB_PASSWORD') ?: 'root';
            $this->sShopURL    = getenv('SHOP_URL') ?: 'localhost';
            $this->sSSLShopURL = getenv('SHOP_URL') ?: 'localhost';
            $this->sAdminEmail = getenv('SHOP_ADMIN') ?: 'webmaster@localhost';
            $this->iDebug      = getenv('SHOP_DEBUG') ?: 0;
        }, $config, $config)();

        $configFile = INSTALLATION_ROOT_PATH . (getenv('SHOP_CONFIG') ?:  '/config/shop.php');
        /** @noinspection PhpIncludeInspection */
        file_exists($configFile) && require $configFile;
    }
}
