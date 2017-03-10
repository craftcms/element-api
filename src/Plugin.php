<?php

namespace craft\elementapi;

use Craft;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use yii\base\Event;

/**
 * Element API plugin.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  2.0
 */
class Plugin extends \craft\base\Plugin
{
    // Properties
    // =========================================================================

    /**
     * @var array The configured API endpoints
     * @see getEndpoints()
     */
    private $_endpoints;

    /**
     * @var array The default Fractal resource adapter configuration
     * @see getDefaultResourceAdapterConfig()
     */
    private $_defaultResourceAdapterConfig;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, [$this, 'registerUrlRules']);
    }

    /**
     * Returns the configured API endpoints.
     *
     * @return array
     */
    public function getEndpoints()
    {
        if ($this->_endpoints !== null) {
            return $this->_endpoints;
        }

        return $this->_endpoints = Craft::$app->getConfig()->get('endpoints', 'elementapi');
    }

    /**
     * Returns the endpoint config for a given URL pattern.
     *
     * @param string $pattern
     *
     * @return callable|array|ResourceAdapterInterface|null
     */
    public function getEndpoint($pattern)
    {
        $endpoints = $this->getEndpoints();

        if (!isset($endpoints[$pattern])) {
            return null;
        }

        return $endpoints[$pattern];
    }

    /**
     * Returns the default endpoint configuration.
     *
     * @return array
     */
    public function getDefaultResourceAdapterConfig(): array
    {
        if ($this->_defaultResourceAdapterConfig !== null) {
            return $this->_defaultResourceAdapterConfig;
        }

        return $this->_defaultResourceAdapterConfig = Craft::$app->getConfig()->get('defaults', 'elementapi');
    }

    /**
     * Registers the site URL rules.
     *
     * @param RegisterUrlRulesEvent $event
     */
    public function registerUrlRules(RegisterUrlRulesEvent $event)
    {
        foreach ($this->getEndpoints() as $pattern => $config) {
            $event->rules[$pattern] = [
                'route' => 'element-api',
                'defaults' => ['pattern' => $pattern],
            ];
        }
    }

    /**
     * Creates a Fractal resource adapter based on the given config.
     *
     * @param array|ResourceAdapterInterface
     *
     * @return ResourceAdapterInterface
     */
    public function createResourceAdapter($config): ResourceAdapterInterface
    {
        if ($config instanceof ResourceAdapterInterface) {
            return $config;
        }

        // Merge in the defaults
        $config = array_merge($this->getDefaultResourceAdapterConfig(), $config);

        if (!isset($config['class'])) {
            // Default to ElementResourceAdapter
            $config['class'] = ElementResourceAdapter::class;
        }

        return Craft::createObject($config);
    }
}
