<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Module;

use fn;
use JsonSerializable;

/**
 * @property-read string $id
 * @property-read string $title
 * @property-read string $url
 * @property-read string $author
 * @property-read array  $settings
 * @property-read array  $block
 * @property-read fn\Cli $cli
 * @property-read array  $metadata
 */
class Module implements JsonSerializable
{
    use fn\DI\PropertiesReadOnlyTrait;

    /**
     * @var string
     */
    public const CONFIG = __DIR__ . '/../../../config/di.php';

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
        return fn\traverse((new Settings($this->get(SETTINGS, [])))->translate($lang));
    }

    public function activate(): bool
    {
        return true;
    }

    public function deactivate(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return $this->get([ID, TITLE, URL, AUTHOR]) + [
            SETTINGS => new Settings($this->get(SETTINGS, [])),
            BLOCKS   => new Blocks($this->get(BLOCKS, [])),
            EVENTS   => new Events
        ];
    }
}
