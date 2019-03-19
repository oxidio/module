<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\DI;

use Generator;
use OxidEsales\Eshop\Core\{
    Config,
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
use Psr\Log\LoggerInterface;
use ReflectionParameter;


/**
 */
class RegistryResolver
{
    /**
     * @see GeneratorResolver
     * @param ReflectionParameter $parameter
     *
     * @return Generator
     */
    public function __invoke(ReflectionParameter $parameter): Generator
    {
        if ($class = $parameter->getClass()) {
            switch ($class->getName()) {
                case Config::class: yield Registry::getConfig(); break;
                case Session::class: yield Registry::getSession(); break;
                case Language::class: yield Registry::getLang(); break;
                case Utils::class: yield Registry::getUtils(); break;
                case UtilsObject::class: yield Registry::getUtilsObject(); break;
                case InputValidator::class: yield Registry::getInputValidator(); break;
                case PictureHandler::class: yield Registry::getPictureHandler(); break;
                case Request::class: yield Registry::getRequest(); break;
                case SeoEncoder::class: yield Registry::getSeoEncoder(); break;
                case SeoDecoder::class: yield Registry::getSeoDecoder(); break;
                case UtilsCount::class: yield Registry::getUtilsCount(); break;
                case UtilsDate::class: yield Registry::getUtilsDate(); break;
                case UtilsFile::class: yield Registry::getUtilsFile(); break;
                case UtilsPic::class: yield Registry::getUtilsPic(); break;
                case UtilsServer::class: yield Registry::getUtilsServer(); break;
                case UtilsString::class: yield Registry::getUtilsString(); break;
                case UtilsUrl::class: yield Registry::getUtilsUrl(); break;
                case UtilsXml::class: yield Registry::getUtilsXml(); break;
                case UtilsView::class: yield Registry::getUtilsView(); break;
                case ControllerClassNameResolver::class: yield Registry::getControllerClassNameResolver(); break;
                case LoggerInterface::class: yield Registry::getLogger(); break;
            }
        }
    }
}
