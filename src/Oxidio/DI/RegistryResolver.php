<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\DI;

use Generator;
use OxidEsales\Eshop\Core\{
    Config,
    ConfigFile,
    InputValidator,
    Language,
    PictureHandler,
    Registry,
    Request,
    Routing\ControllerClassNameResolver,
    SeoDecoder,
    SeoEncoder,
    Session,
    Utils,
    UtilsCount,
    UtilsDate,
    UtilsFile,
    UtilsObject,
    UtilsPic,
    UtilsServer,
    UtilsString,
    UtilsUrl,
    UtilsView,
    UtilsXml
};
use Psr\{Container\ContainerInterface, Log\LoggerInterface};
use ReflectionParameter;
use fn;

/**
 * @property-read ContainerInterface $container
 */
class RegistryResolver
{
    use fn\PropertiesTrait\ReadOnly;

    /**
     * @var string[]
     */
    public const DEFINITIONS = [
        Config::class => 'getConfig',
        Session::class => 'getSession',
        Language::class => 'getLang',
        Utils::class => 'getUtils',
        UtilsObject::class => 'getUtilsObject',
        InputValidator::class => 'getInputValidator',
        PictureHandler::class => 'getPictureHandler',
        Request::class => 'getRequest',
        SeoEncoder::class => 'getSeoEncoder',
        SeoDecoder::class => 'getSeoDecoder',
        UtilsCount::class => 'getUtilsCount',
        UtilsDate::class => 'getUtilsDate',
        UtilsFile::class => 'getUtilsFile',
        UtilsPic::class => 'getUtilsPic',
        UtilsServer::class => 'getUtilsServer',
        UtilsString::class => 'getUtilsString',
        UtilsUrl::class => 'getUtilsUrl',
        UtilsXml::class => 'getUtilsXml',
        UtilsView::class => 'getUtilsView',
        ControllerClassNameResolver::class => 'getControllerClassNameResolver',
        LoggerInterface::class => 'getLogger',
        ConfigFile::class => 'get',
    ];

    /**
     * @see $container
     * @return ContainerInterface
     */
    public function resolveContainer(): ContainerInterface
    {
        $defs = fn\traverse(static::DEFINITIONS, static function (string $method, string $class) {
            return static function () use($method, $class) {
                return Registry::$method($class);
            };
        });
        return fn\di($defs);
    }

    /**
     * @see GeneratorResolver
     * @param ReflectionParameter $parameter
     *
     * @return Generator
     */
    public function __invoke(ReflectionParameter $parameter): Generator
    {
        if (($class = $parameter->getClass()) && $this->container->has($class->getName())) {
            yield $this->container->get($class->getName());
        }
    }
}
