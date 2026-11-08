<?php

declare(strict_types=1);

namespace CraftCms\ElementApi;

use CraftCms\Cms\Plugin\Plugin as BasePlugin;
use CraftCms\Cms\Plugin\PluginSettings;
use CraftCms\Cms\Utility\Utilities\ClearCaches;
use Override;

use function CraftCms\Cms\t;

/**
 * Element API plugin.
 *
 * @method Settings getSettings()
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 2.0
 */
class Plugin extends BasePlugin
{
    #[Override]
    public function register(): void
    {
        // Let classes type-hint the settings instead of looking up the plugin. This is bound here rather
        // than in boot(), because the endpoint routes are registered before boot() runs.
        $this->app->singleton(Settings::class, fn () => $this->getSettings());
    }

    public function boot(): void
    {
        ClearCaches::addTag('element-api', t('Element API responses', category: 'element-api'));
    }

    #[Override]
    protected static function createSettings(): ?PluginSettings
    {
        return new Settings;
    }
}
