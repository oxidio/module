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
use OxidEsales\EshopCommunity\Internal\Container\BootstrapContainerFactory;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use OxidEsales\Facts\Config\ConfigFile as FactsConfigFile;
use Symfony\Component\Debug\Debug;

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
     * @param ?BasicContextInterface $context
     */
    public static function bootstrap($config, BasicContextInterface $context = null): void
    {
        if (!class_exists(Dotenv::class)) {
            return;
        }
        $context = $context ?: BootstrapContainerFactory::getBootstrapContainer()->get(BasicContextInterface::class);
        Dotenv::create($context->getShopRootPath())->overload();
        Closure::bind(function (array $configParamNames, BasicContextInterface $context) {
            $this->sShopDir    = $context->getSourcePath() . '/';
            $this->sCompileDir = $context->getSourcePath() . '/tmp/';
            $this->dbHost      = getenv('DB_HOST') ?: 'localhost';
            $this->dbPort      = getenv('DB_PORT') ?: 3306;
            $this->dbName      = getenv('DB_NAME') ?: 'project';
            $this->dbUser      = getenv('DB_USER') ?: 'root';
            $this->dbPwd       = getenv('DB_PASSWORD') ?: 'root';
            $this->sShopURL    = getenv('SHOP_URL') ?: 'localhost';
            $this->sSSLShopURL = getenv('SHOP_URL') ?: 'localhost';
            $this->sAdminEmail = getenv('SHOP_ADMIN') ?: 'webmaster@localhost';
            $this->iDebug      = getenv('SHOP_DEBUG') ?: 0;
            if ($this->iDebug < 0 && class_exists(Debug::class)) {
                /** @noinspection ForgottenDebugOutputInspection */
                Debug::enable(E_ALL & ~E_DEPRECATED);
            }

            $isSandBox = (bool) getenv('SHOP_SANDBOX');
            $sandBox = $isSandBox ? 'SANDBOX_' : '';
            $defaultIndex = $isSandBox ? 'sandbox' : 'prod';

            foreach ($configParamNames as $envName => $param) {
                if (($value = getenv("SHOP_CONFIG_{$sandBox}{$envName}")) === false) {
                    $value = $param[$defaultIndex] ?? null;
                }
                $value === null ||  $this->_aConfigParams[$param['name']] = $value;
            }
        }, $config, $config)(self::CONFIG_PARAM_NAMES, $context);
    }
}
