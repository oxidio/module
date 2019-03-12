<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio;

use fn;
use JsonSerializable;
use Oxidio\Module\Blocks;
use Oxidio\Module\Settings;
use Psr\Container\ContainerInterface;

/**
 * @property-read string $id
 * @property-read string $title
 * @property-read string $url
 * @property-read string $author
 * @property-read array  $settings
 * @property-read array  $block
 * @property-read fn\Cli $cli
 */
class Module implements JsonSerializable
{
    use fn\DI\PropertiesReadOnlyTrait;

    protected const CONFIG = __DIR__ . '/../../config/di.php';

    /**
     * @var static[]
     */
    protected static $cache = [];

    /**
     * @var fn\DI\Container
     */
    protected $container;

    /**
     * @param fn\DI\Container $container
     */
    public function __construct(fn\DI\Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $config
     *
     * @return static
     */
    public static function instance(string $config = null)
    {
        $config = $config ?: static::CONFIG;
        if (!isset(static::$cache[$config])) {
            static::$cache[$config] = new static(fn\di(
                self::CONFIG,
                $config,
                call_user_func(require fn\VENDOR_DIR . 'autoload.php', function (ContainerInterface $container) {
                    return $container;
                })
            ));
        }
        return static::$cache[$config];
    }

    /**
     * @param string|iterable $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function get($name, $default = null)
    {
        if (is_iterable($name)) {
            return fn\traverse($name, function($default, $name) {
                if (is_numeric($name)) {
                    $name    = $default;
                    $default = fn\mapNull();
                }
                return fn\mapKey($name)->andValue($this->$name ?? $default);
            });
        }
        return $this->$name ?? $default;
    }

    public function __invoke()
    {
        return call_user_func($this->cli);
    }

    /**
     * @param string $lang
     *
     * @return array
     */
    public function getTranslations(string $lang): array
    {
        return fn\traverse((new Settings($this->get(Module\SETTINGS, [])))->translate($lang));
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return $this->get([Module\ID, Module\TITLE, Module\URL, Module\AUTHOR]) + [
            Module\SETTINGS => new Settings($this->get(Module\SETTINGS, [])),
            Module\BLOCKS   => new Blocks($this->get(Module\BLOCKS, [])),
        ];
    }
}
