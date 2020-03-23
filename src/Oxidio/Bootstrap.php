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
    private const CONFIG_PARAM_NAMES = [
        'PASSWORD_LENGTH' => [
            'name' => 'iPasswordLength',
            'sandbox' => 1,
            //'prod' => null, // => default = 6
        ],
        'EMAIL_VALIDATION_RULE' => [
            'name' => 'sEmailValidationRule',
            'sandbox' => '/.*/',
            // 'prod' => null, // default = '/^([\w+\-.])+\@([\w\-.])+\.([A-Za-z]{2,64})$/i',
        ],
    ];

    /**
     * @param Config|BootstrapConfigFileReader|FactsConfigFile|ShopConfigFile $config
     */
    public static function bootstrap($config): void
    {
        if (class_exists(Dotenv::class)) {
            Dotenv::create(INSTALLATION_ROOT_PATH)->overload();
        }

        Closure::bind(function (array $configParamNames) {
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

            $isSandBox = (bool) getenv('SHOP_SANDBOX');
            $sandBox = $isSandBox ? 'SANDBOX_' : '';
            $defaultIndex = $isSandBox ? 'sandbox' : 'prod';

            foreach ($configParamNames as $envName => $param) {
                if (($value = getenv("SHOP_CONFIG_{$sandBox}{$envName}")) === false) {
                    $value = $param[$defaultIndex] ?? null;
                }
                $value === null ||  $this->_aConfigParams[$param['name']] = $value;
            }

            $configFile = INSTALLATION_ROOT_PATH . (getenv('SHOP_CONFIG') ?:  '/config/shop.php');
            /** @noinspection PhpIncludeInspection */
            file_exists($configFile) && require $configFile;

        }, $config, $config)(self::CONFIG_PARAM_NAMES);
    }
}
