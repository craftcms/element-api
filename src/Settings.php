<?php

namespace craft\elementapi;

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
    public array $endpoints = [];

    /**
     * Returns the default endpoint configuration.
     *
     * @return array The default endpoint configuration.
     * @since 2.6.0
     */
    public function getDefaults(): array
    {
        return is_callable($this->defaults) ? call_user_func($this->defaults) : $this->defaults;
    }
}
