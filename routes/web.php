<?php

use CraftCms\ElementApi\Http\Controllers\ElementApiController;
use CraftCms\ElementApi\Plugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

// Register a route for each endpoint in config/craft/element-api.php.
// Patterns use Laravel route syntax, e.g. `news/{entryId}`.
foreach (app(Plugin::class)->getSettings()->endpoints as $pattern => $config) {
    $route = Route::match(['GET', 'HEAD', 'OPTIONS'], $pattern, ElementApiController::class)
        ->defaults('elementApiEndpoint', $pattern)
        // A read-only JSON API doesn't need cookies or a session, and shouldn't set any
        // (a Set-Cookie header stops CDNs and reverse proxies from caching the response).
        ->withoutMiddleware([
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            PreventRequestForgery::class,
        ]);

    // A closure that type-hints a route parameter as `int` only matches digits for it,
    // so `news/abc.json` is a 404 rather than an error.
    if ($config instanceof Closure) {
        foreach ((new ReflectionFunction($config))->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && $type->getName() === 'int' && str_contains($pattern, '{' . $parameter->getName() . '}')) {
                $route->whereNumber($parameter->getName());
            }
        }
    }
}
