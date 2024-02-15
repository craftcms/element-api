<?php

namespace craft\elementapi;

use Craft;
use craft\base\Model;
use craft\elementapi\resources\ElementResource;
use craft\events\RegisterCacheOptionsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\utilities\ClearCaches;
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
    /**
     * @var array The default Fractal resource adapter configuration
     * @see getDefaultResourceAdapterConfig()
     */
    private array $_defaultResourceAdapterConfig;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            [$this, 'registerUrlRules']
        );

        Event::on(
            ClearCaches::class,
            ClearCaches::EVENT_REGISTER_TAG_OPTIONS,
            function(RegisterCacheOptionsEvent $event) {
                $event->options[] = [
                    'tag' => 'element-api',
                    'label' => Craft::t('element-api', 'Element API responses'),
                ];
            }
        );
    }

    /**
     * Returns the endpoint config for a given URL pattern.
     *
     * @param string $pattern
     * @return callable|array|ResourceAdapterInterface|null
     */
    public function getEndpoint(string $pattern)
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

        return $this->_defaultResourceAdapterConfig = $this->getSettings()->getDefaults();
    }

    /**
     * Registers the site URL rules.
     *
     * @param RegisterUrlRulesEvent $event
     */
    public function registerUrlRules(RegisterUrlRulesEvent $event): void
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
     * @param array|ResourceInterface|ResourceAdapterInterface $config
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

    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }
}
