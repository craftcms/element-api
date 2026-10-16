<?php

namespace CraftCms\ElementApi;

use CraftCms\Cms\Plugin\PluginSettings;
use CraftCms\Cms\Utility\Utilities\ClearCaches;

use function CraftCms\Cms\t;

/**
 * Element API plugin.
 *
 * @method Settings getSettings()
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 */
class Plugin extends \CraftCms\Cms\Plugin\Plugin
{
    /**
     * @inheritdoc
     */
    public function boot(): void
    {
        // Let classes type-hint the settings instead of looking up the plugin
        $this->app->singleton(Settings::class, fn() => $this->getSettings());

        ClearCaches::addTag('element-api', t('Element API responses', category: 'element-api'));
    }

    /**
     * @inheritdoc
     */
    protected static function createSettings(): ?PluginSettings
    {
        return new Settings();
    }
}
