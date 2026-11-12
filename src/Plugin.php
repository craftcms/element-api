<?php

declare(strict_types=1);

namespace CraftCms\ElementApi;

use CraftCms\Cms\Plugin\Plugin as BasePlugin;
use CraftCms\Cms\Plugin\PluginSettings;
use CraftCms\Cms\Utility\Utilities\ClearCaches;
use CraftCms\ElementApi\Http\EndpointRoutes;
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
        // Let classes type-hint the settings instead of looking up the plugin.
        $this->app->singleton(Settings::class, fn () => $this->getSettings());
    }

    public function boot(): void
    {
        ClearCaches::addTag('element-api', t('Element API responses', category: 'element-api'));

        if (! $this->app->routesAreCached()) {
            // Only the `craft` group (site resolution, response headers, maintenance mode), not `craft.web`:
            // a read-only JSON API doesn't need sessions, cookies, CSRF protection or template routing, and
            // shouldn't set cookies (a Set-Cookie header stops CDNs and reverse proxies from caching the response).
            $this->app['router']->middleware('craft')
                ->group(fn () => $this->app->make(EndpointRoutes::class)->register());
        }
    }

    #[Override]
    protected static function createSettings(): ?PluginSettings
    {
        return new Settings;
    }
}
