<?php

namespace craft\elementapi;

use Craft;
use craft\base\Model;

/**
 * Element API plugin.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 */
class Settings extends Model
{
    /**
     * @var callable|array The default endpoint configuration.
     */
    public $defaults = [];

    /**
     * @var array The endpoint configurations.
     */
    public $endpoints = [];

    /**
     * Returns the default endpoint configuration.
     *
     * @return array The default endpoint configuration.
     * @since 2.6.0
     */
    public function getDefaults()
    {
        return is_callable($this->defaults) ? call_user_func($this->defaults) : $this->defaults;
    }

    /**
     * Returns the default data cache key that should be used for endpoint responses.
     *
     * @return string The default data cache key.
     */
    public static function cacheKey(): string
    {
        $request = Craft::$app->getRequest();

        return implode(':', [
            'elementapi',
            Craft::$app->getSites()->getCurrentSite()->id,
            $request->getPathInfo(),
            $request->getQueryStringWithoutPath(),
        ]);
    }
}
