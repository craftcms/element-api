# Upgrading Element API for Craft 6

This guide lists what changed when Element API moved to Craft 6, and what to update in your project. Because your endpoints live in a config file, most of the work is there.

## Requirements

- Craft CMS 6
- PHP 8.5 or later
- The plugin no longer needs `craftcms/yii2-adapter`. Your project may still use it for other plugins and modules.

## Upgrade steps

1. Upgrade your project to Craft 6 first.
2. Require the Craft 6 version of the plugin: `composer require craftcms/element-api`.
3. Move `config/element-api.php` to `config/craft/element-api.php`. (For a new project, `php artisan vendor:publish --tag=element-api` creates an example file there.)
4. Update the Craft 5 classes used in your endpoint functions, using the changes below. Your endpoint patterns keep working, but you can move them to Laravel route syntax.
5. Optionally, move your endpoint functions into invokable classes, so you can run `php artisan config:cache`. See [Endpoint classes](#endpoint-classes).
6. Clear the Element API cache: go to **Utilities** → **Caches**, and invalidate **Element API responses** under **Invalidate Data Caches**.

## Breaking changes

### Config file location

| Before | After |
| --- | --- |
| `config/element-api.php` | `config/craft/element-api.php` |

### Endpoint URL patterns

Endpoints are registered as Laravel routes. Craft 5 (Yii) patterns still work: Element API converts them, keeping their inline regular expressions and the `{handle}`, `{slug}` and `{uid}` tokens. New endpoints can use Laravel route syntax instead.

| Craft 5 pattern (still supported) | Laravel route syntax |
| --- | --- |
| `'news/<entryId:\d+>.json'` | `'news/{entryId}.json'`, with an `int $entryId` parameter |
| `'ingredients/<slug:{slug}>.json'` | `'ingredients/{slug}.json'` |

- A Laravel-style parameter matches any value except `/`. One your function type-hints as `int` only matches digits (so `news/abc.json` is a 404).
- Parameters are still passed to your function by name. Your function can now also type-hint services and `Illuminate\Http\Request`.
- As before, endpoints also answer under each site’s base URL path (`de/news.json` for a site at `https://example.com/de/`), with that site as the current site.
- Endpoints answer `GET`, `HEAD` and `OPTIONS` requests. They used to answer any method.

Before:

```php
'news/<entryId:\d+>.json' => function($entryId) {
    return [
        'elementType' => craft\elements\Entry::class,
        'criteria' => ['id' => $entryId],
        'one' => true,
    ];
},
```

After:

```php
'news/{entryId}.json' => NewsEntryEndpoint::class,
```

```php
use CraftCms\Cms\Entry\Elements\Entry;

class NewsEntryEndpoint
{
    public function __invoke(int $entryId): array
    {
        return [
            'elementType' => Entry::class,
            'criteria' => ['id' => $entryId],
            'one' => true,
        ];
    }
}
```

### Endpoint classes

Endpoints can now be the name of an invokable class instead of a function. Its `__invoke()` method gets the route parameters by name, can type-hint services and `Illuminate\Http\Request`, and returns the endpoint’s settings, just like a function. Functions still work, but Laravel can’t cache a config file that contains them, so `php artisan config:cache` only works once every endpoint (and `defaults`) is a class name or plain value.

### Craft 5 classes and APIs in your endpoint functions

Your config file runs on Craft 6 now, so anything it calls has to exist there. Without the adapter, update:

| Before | After |
| --- | --- |
| `craft\elements\Entry` | `CraftCms\Cms\Entry\Elements\Entry` |
| `craft\elements\Asset` | `CraftCms\Cms\Asset\Elements\Asset` |
| `craft\elements\User` | `CraftCms\Cms\User\Elements\User` |
| `craft\helpers\UrlHelper::url()` | `CraftCms\Cms\Support\Url::url()` |
| `Craft::$app->getResponse()->getHeaders()->set('X-Foo', 'bar')` | `CraftCms\Cms\Support\Facades\ResponseHeaders::add('X-Foo', 'bar')` |
| `Craft::$app->request->getQueryParam('q')` | `$request->query('q')`, with `Illuminate\Http\Request $request` as an argument of your function |
| `'orderBy' => 'id asc'` (deprecated in Craft 6) | `'orderBy' => ['id' => SORT_ASC]` |

Categories, tags and global sets are deprecated, and have no Craft 6 element types. Move their content to entries.

Before:

```php
'news.json' => function() {
    Craft::$app->getResponse()->getHeaders()->set('Access-Control-Allow-Origin', '*');

    return [
        'elementType' => craft\elements\Entry::class,
        'criteria' => ['section' => 'news'],
    ];
},
```

After:

```php
'news.json' => NewsEndpoint::class,
```

```php
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Facades\ResponseHeaders;

class NewsEndpoint
{
    public function __invoke(): array
    {
        ResponseHeaders::add('Access-Control-Allow-Origin', '*');

        return [
            'elementType' => Entry::class,
            'criteria' => ['section' => 'news'],
        ];
    }
}
```

### Events

The `EVENT_BEFORE_SEND_DATA` event on `craft\elementapi\controllers\DefaultController` was replaced by a Laravel event, and `craft\elementapi\DataEvent` was removed.

| Before | After |
| --- | --- |
| `DefaultController::EVENT_BEFORE_SEND_DATA` (`beforeSendData`) | `CraftCms\ElementApi\Events\SendingData` |
| `craft\elementapi\DataEvent` | `CraftCms\ElementApi\Events\SendingData` (the event itself) |

Before:

```php
use craft\elementapi\controllers\DefaultController;
use craft\elementapi\DataEvent;
use yii\base\Event;

Event::on(
    DefaultController::class,
    DefaultController::EVENT_BEFORE_SEND_DATA,
    function(DataEvent $event) {
        $scope = $event->payload;
    }
);
```

After:

```php
use CraftCms\ElementApi\Events\SendingData;
use Illuminate\Support\Facades\Event;

Event::listen(function(SendingData $event): void {
    $scope = $event->payload;
});
```

Listeners receive the event directly: there is no `sender`. Register them in a service provider’s `boot()` method (for a project, `app/Providers/AppServiceProvider.php`).

### Classes and namespaces

The plugin now follows Craft 6 conventions: the namespace is `CraftCms\ElementApi` (PSR-4 root `src/`) and classes are organized by role. Update any code that references the old classes.

| Before | After | Change |
| --- | --- | --- |
| `craft\elementapi\Plugin` | `CraftCms\ElementApi\Plugin` | Extends `CraftCms\Cms\Plugin\Plugin`. The endpoint lookup and resource creation moved out of it. |
| `craft\elementapi\Settings` | `CraftCms\ElementApi\Settings` | Extends `CraftCms\Cms\Plugin\PluginSettings`. |
| `craft\elementapi\controllers\DefaultController` | `CraftCms\ElementApi\Http\Controllers\ElementApiController` | An invokable controller with no base class. |
| `craft\elementapi\resources\ElementResource` | `CraftCms\ElementApi\Resources\ElementResource` | Extends `CraftCms\Cms\Component\Component` instead of `yii\base\BaseObject`. |
| `Plugin::createResource()` | `CraftCms\ElementApi\Resources\ResourceFactory::create()` | Resolve it from the container. |
| `craft\elementapi\ResourceAdapterInterface` | `CraftCms\ElementApi\Contracts\ResourceAdapter` | Renamed. |
| `craft\elementapi\ElementTransformer` | `CraftCms\ElementApi\Transformers\ElementTransformer` | |
| `craft\elementapi\JsonFeedV1Serializer` | `CraftCms\ElementApi\Serializers\JsonFeedV1Serializer` | Needs a `Request` and `Sites`: get it with `app(JsonFeedV1Serializer::class)`. |
| `craft\elementapi\PaginatorAdapter` | `CraftCms\ElementApi\Support\PaginatorAdapter` | Takes a `Request` as its fourth argument. |
| `craft\elementapi\DataEvent` | removed | See [Events](#events). |

If your endpoint sets `'class'` to your own resource class, it is created with `new` and your config array when it extends `CraftCms\Cms\Component\Component`, and otherwise resolved from the container and configured with the array.

## Behavior changes

- **No cookies:** responses no longer set a session or `XSRF-TOKEN` cookie, so reverse proxies and CDNs can cache them.
- **No sessions:** endpoints run in Craft’s `craft` middleware group but not `craft.web`, so they don’t start a session, read the session cookie or run the queue. An endpoint function can’t see the user who is signed in to the control panel. Previews still work, and skip the cache as before.
- **Cache duration:** `'cache' => true` caches for the `cacheDuration` general config setting (or until a relevant element changes), as before. `0` still means until a relevant element changes.
- **JSONP:** unchanged. `jsonOptions` and `pretty` are still ignored when `callback` is set.
- **Logging:** failed requests are logged through Laravel’s logger.
- **Image transforms:** still generated before an endpoint responds, as in Craft 5, so responses and the cache contain final transform URLs.
- **Deployment:** `php artisan config:cache` only works when your endpoints are classes rather than functions. Run `php artisan route:cache` again after you change your endpoints or a site’s base URL.

## Removed

- The `first` endpoint setting, deprecated since Element API 2.0. Use `one` instead: an endpoint that still sets `first` now returns every matching element.
- `Plugin::registerUrlRules()` and the `EVENT_REGISTER_SITE_URL_RULES` listener. Endpoints are registered as Laravel routes by `CraftCms\ElementApi\Http\EndpointRoutes`.
- The `pageParam` check against the `p` path parameter. Craft 6 has no path parameter.
- The `stubs/BaseYii.stub` PHPStan stub.
