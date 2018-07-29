<?php

namespace craft\elementapi;

use Craft;
use craft\elementapi\resources\ElementResource;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use League\Fractal\Resource\ResourceInterface;
use yii\base\Event;

/**
 * Element API plugin.
 *
 * @property Settings $settings
 * @method Settings getSettings()
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 */
class Plugin extends \craft\base\Plugin
{
    // Properties
    // =========================================================================

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
     * Returns the endpoint config for a given URL pattern.
     *
     * @param string $pattern
     * @return callable|array|ResourceAdapterInterface|null
     */
    public function getEndpoint($pattern)
    {
        return $this->getSettings()->endpoints[$pattern] ?? null;
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

        return $this->_defaultResourceAdapterConfig = $this->getSettings()->defaults;
    }

    /**
     * Registers the site URL rules.
     *
     * @param RegisterUrlRulesEvent $event
     */
    public function registerUrlRules(RegisterUrlRulesEvent $event)
    {
        foreach ($this->getSettings()->endpoints as $pattern => $config) {
            $event->rules[$pattern] = [
                'route' => 'element-api',
                'defaults' => ['pattern' => $pattern],
            ];
        }
    }

    /**
     * Creates a Fractal resource based on the given config.
     *
     * @param array|ResourceInterface|ResourceAdapterInterface
     * @return ResourceInterface
     */
    public function createResource($config): ResourceInterface
    {
        if ($config instanceof ResourceInterface) {
            return $config;
        }

        if ($config instanceof ResourceAdapterInterface) {
            return $config->getResource();
        }

        if (!isset($config['class'])) {
            // Default to ElementResourceAdapter
            $config['class'] = ElementResource::class;
        }

        /** @var ResourceInterface|ResourceAdapterInterface $resource */
        $resource = Craft::createObject($config);

        if ($resource instanceof ResourceAdapterInterface) {
            $resource = $resource->getResource();
        }

        return $resource;
    }

    // Protected Methods
    // =========================================================================

    protected function createSettingsModel()
    {
        return new Settings();
    }
}
