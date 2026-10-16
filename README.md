# Element API for Craft CMS

This plugin makes it easy to create a JSON API for your entries (and other element types) in [Craft CMS](http://buildwithcraft.com).

It’s powered by Phil Sturgeon’s excellent [Fractal](http://fractal.thephpleague.com/) package.

## Requirements

This plugin requires Craft CMS 6.0.0-alpha.1 or later, and PHP 8.5 or later. It doesn’t need the Yii 2 adapter.

Using Craft 4 or 5? Stay on the 4.x line of this plugin. Upgrading a Craft 5 project that already uses Element API? See the [upgrade guide](UPGRADE.md).

## Installation

You can install this plugin from the Plugin Store or with Composer.

#### From the Plugin Store

Go to the Plugin Store in your project’s Control Panel and search for “Element API”. Then click on the “Install” button in its modal window.

#### With Composer

Open your terminal and run the following commands:

```bash
# go to the project directory
cd /path/to/my-project.test

# tell Composer to load the plugin
composer require craftcms/element-api

# tell Craft to install the plugin
php artisan craft:plugin:install element-api
```

## Setup

To define your API endpoints, create a new `element-api.php` file within your `config/craft/` folder. This file should return an array with an `endpoints` key, which defines your site’s API endpoints.

Within the `endpoints` array, keys are URL patterns ([Laravel route syntax](https://laravel.com/docs/routing#route-parameters), such as `news/{entryId}.json`), and values are functions that define the endpoint configurations.

```php
<?php

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Url;

return [
    'endpoints' => [
        'news.json' => function() {
            return [
                'elementType' => Entry::class,
                'criteria' => ['section' => 'news'],
                'transformer' => function(Entry $entry) {
                    return [
                        'id' => $entry->id,
                        'title' => $entry->title,
                        'url' => $entry->url,
                        'jsonUrl' => Url::url("news/$entry->id.json"),
                        'summary' => $entry->summary,
                    ];
                },
            ];
        },
        'news/{entryId}.json' => function(int $entryId) {
            return [
                'elementType' => Entry::class,
                'criteria' => ['id' => $entryId],
                'one' => true,
                'transformer' => function(Entry $entry) {
                    return [
                        'title' => $entry->title,
                        'url' => $entry->url,
                        'summary' => $entry->summary,
                        'body' => $entry->body,
                    ];
                },
            ];
        },
    ]
];

```

### Endpoint Configuration Settings

Endpoint configuration arrays can contain the following settings:

#### `class`

The class name of the Fractal resource that should be used to serve the request. If this isn’t set, it will default to `CraftCms\ElementApi\Resources\ElementResource`. (All of the following configuration settings are specific to that default class.)

#### `elementType` _(Required)_

The class name of the element type that the API should be associated with. Craft’s built-in element type classes include:

- `CraftCms\Cms\Asset\Elements\Asset`
- `CraftCms\Cms\Entry\Elements\Entry`
- `CraftCms\Cms\User\Elements\User`
- `CraftCms\Cms\Address\Elements\Address`

> **Note**  
> As of Craft 6.0.0-alpha.20, categories, tags and global sets only exist as legacy classes in the Yii 2 adapter (`craft\elements\Category`, `craft\elements\Tag`, `craft\elements\GlobalSet`), so they can’t be queried without it yet.

```php
'elementType' => CraftCms\Cms\Entry\Elements\Entry::class,
````

#### `criteria`

An array of parameters that should be set on the [Element Query](https://docs.craftcms.com/v3/element-queries.html) that will be fetching the elements.

```php
'criteria' => [
    'section' => 'news',
    'type' => 'article',
],
```

#### `contentType`

The content type the endpoint responses should have.

```php
'contentType' => 'application/foo+json',
```

By default, the content type will be:

- `application/javascript` for endpoints that define a JSONP [callback](#callback)
- `application/feed+json` for endpoints where the [serializer](#serializer) is set to `jsonFeed`
- `application/json` for everything else 

#### `transformer`

The [transformer](http://fractal.thephpleague.com/transformers/) that should be used to define the data that should be returned for each element. If you don’t set this, the default transformer will be used, which includes all of the element’s direct attribute values, but no custom field values.

```php
// Can be set to a function
'transformer' => function(CraftCms\Cms\Entry\Elements\Entry $entry) {
    return [
        'title' => $entry->title,
        'id' => $entry->id,
        'url' => $entry->url,
    ];
},

// Or a string/array that defines a Transformer class configuration
'transformer' => 'MyTransformerClassName',

// Or a Transformer class instance
'transformer' => new MyTransformerClassName(),
```

Your custom transformer class would look something like this:

```php
<?php

use CraftCms\Cms\Entry\Elements\Entry;
use League\Fractal\TransformerAbstract;

class MyTransformerClassName extends TransformerAbstract
{
    public function transform(Entry $entry)
    {
        return [
            // ...
        ];
    }
}
```

#### `one`

Whether only the first matching element should be returned. This is set to `false` by default, meaning that _all_ matching elements will be returned.

```php
'one' => true,
```

#### `paginate`

Whether the results should be paginated. This is set to `true` by default, meaning that only a subset of the matched elements will be included in each response, accompanied by additional metadata that describes pagination information.

```php
'paginate' => false,
```

#### `elementsPerPage`

The max number of elements that should be included in each page, if pagination is enabled. By default this is set to 100.

```php
'elementsPerPage' => 10,
```

#### `pageParam`

The query string param name that should be used to identify which page is being requested. By default this is set to `'page'`.

```php
'pageParam' => 'pg',
```

#### `resourceKey`

The key that the elements should be nested under in the response data. By default this will be `'data'`.

```php
'resourceKey' => 'entries',
```

#### `meta`

Any custom meta values that should be included in the response data.

```php
'meta' => [
    'description' => 'Recent news from Happy Lager',
],
```

#### `serializer`

The [serializer](http://fractal.thephpleague.com/serializers/) that should be used to format the returned data.

Possible values are:

- `'array'` _(default)_ – formats data using the [ArraySerializer](http://fractal.thephpleague.com/serializers/#arrayserializer).
- `'dataArray'` – formats data using the [DataArraySerializer](http://fractal.thephpleague.com/serializers/#dataarrayserializer).
- `'jsonApi'` – formats data using the [JsonApiSerializer](http://fractal.thephpleague.com/serializers/#jsonapiserializer).
- `'jsonFeed'` – formats data based on [JSON Feed V1.1](https://www.jsonfeed.org/version/1.1/) (see the [JSON Feed](#json-feed) example below).
- A custom serializer instance.

#### `includes`

The [include names](http://fractal.thephpleague.com/transformers/#including-data) that should be included for the current request, if any.

```php
'news.json' => function(\Illuminate\Http\Request $request) {
    return [
        'includes' => (array) $request->query('include'),
        // ...
    ];
},
```

Note that this setting requires a custom transformer class that’s prepped to handle includes:

```php
class MyTransformerClassName extends TransformerAbstract
{
    protected $availableIncludes = ['author'];

    public function includeAuthor(Entry $entry)
    {
        return $this->item($entry->author, function(User $author) {
            return [
                'id' => $author->id,
                'name' => $author->name,
            ];
        });
    }

    // ...
}
```

#### `excludes`

The [include names](http://fractal.thephpleague.com/transformers/#including-data) that should be excluded for the current request, which would otherwise have been included (e.g. if they were listed as a default include), if any.

```php
'excludes' => 'author',
```

Like [`includes`](#includes), this setting requires a custom transformer class.

#### `callback`

If this is set, a JSONP response will be returned with an `application/javascript` content type, using this setting value as the callback function.

For example, if you set this to:

```php
'callback' => 'foo',
```

Then the response will look like:

```javascript
foo({ /* ... */ });
```

Note that if you set this, the `jsonOptions` and `pretty` settings will be ignored. 

#### `jsonOptions`

The value of the `$options` argument that will be passed to [`json_encode()`](http://php.net/manual/en/function.json-encode.php) when preparing the response. By default `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE` will be passed.

```php
'jsonOptions' => JSON_UNESCAPED_UNICODE,
```

#### `pretty`

Shortcut for adding `JSON_PRETTY_PRINT` to `jsonOptions`.

```php
'pretty' => true,
```

#### `cache`

Whether the output should be cached, and for how long.

Possible values are:

- `true` _(default)_ – results are cached for the duration specified by the `cacheDuration` Craft config setting, or until a relevant element is saved or deleted.
- `false` – results are never cached.
- an integer – results are cached for the given number of seconds.
- a [interval spec](http://www.php.net/manual/en/dateinterval.construct.php) string – results are cached for the duration specified.

Note that the [`SendingData` event](#events) does not get triggered when the cache is warm.

```php
'cache' => 'PT1M', // one minute
```

#### `cacheKey`

The key that responses should be cached with, if something custom is needed.

### Dynamic URL Patterns

URL patterns use [Laravel route syntax](https://laravel.com/docs/routing#route-parameters). Wrap a parameter in curly braces to make it dynamic: the pattern “`news/{entryId}.json`” will match URLs like `news/100.json`.

Any parameters in the URL pattern are passed to the endpoint config function by name. For example, if a URL pattern contains `{entryId}`, then you can add an `$entryId` argument to your endpoint config function, and whatever matches the URL will be passed to `$entryId`. The function can also type-hint services and the request, such as `Illuminate\Http\Request $request`.

If you type-hint a parameter as `int`, the route only matches digits for it, so `news/abc.json` is a 404.

```php
'news/{entryId}.json' => function(int $entryId) {
    return [
        'elementType' => CraftCms\Cms\Entry\Elements\Entry::class,
        'criteria' => ['id' => $entryId],
        'one' => true,
    ];
},
```

Endpoints answer `GET`, `HEAD` and `OPTIONS` requests.

### Response Headers

To add response headers (for example CORS headers), call `ResponseHeaders::add()` from your endpoint function. Craft applies them to the response, whether it comes from the cache or not.

```php
use CraftCms\Cms\Support\Facades\ResponseHeaders;

'news.json' => function() {
    ResponseHeaders::add('Access-Control-Allow-Origin', '*');

    return [
        // ...
    ];
},
```

Endpoints send `X-Robots-Tag: none` unless you set that header yourself. Responses don’t set any cookies.

### Events

Element API dispatches a `CraftCms\ElementApi\Events\SendingData` event before an endpoint’s data is serialized and sent. Its `payload` property is the Fractal `Scope` for the response.

```php
use CraftCms\ElementApi\Events\SendingData;
use Illuminate\Support\Facades\Event;

Event::listen(function(SendingData $event): void {
    $scope = $event->payload;
});
```

### Caching and Deployment Notes

- Cached responses are tagged with `element-api`. Use **Utilities** → **Clear Caches** → **Element API responses** to clear them. Saving or deleting a relevant element clears them automatically.
- Your endpoint functions are closures, which Laravel can’t serialize, so don’t run `php artisan config:cache` in a project that uses them.
- If you cache routes with `php artisan route:cache`, run it again (or `route:clear`) whenever you change your endpoints.

### Setting Default Configuration Settings

You can specify default values for your endpoint configuration settings by adding a `defaults` key alongside your `endpoints` key (**not** within it).

```php
use CraftCms\Cms\Entry\Elements\Entry;

return [
    'defaults' => [
        'elementType' => Entry::class,
        'elementsPerPage' => 10,
        'pageParam' => 'pg',
        'transformer' => function(Entry $entry) {
            return [
                'title' => $entry->title,
                'id' => $entry->id,
                'url' => $entry->url,
            ];
        },
    ],

    'endpoints' => [
        'news.json' => function() {
            return [
                'criteria' => ['section' => 'news'],
            ]
        },
        'news/{entryId}.json' => function(int $entryId) {
            return [
                'criteria' => ['id' => $entryId],
                'one' => true,
            ];
        },
    ]
];
```

## Examples

Here are a few endpoint examples, and what their response would look like.
  
### Paginated Entry Index Endpoint

```php
'ingredients.json' => function() {
    return [
        'criteria' => ['section' => 'ingredients'],
        'elementsPerPage' => 10,
        'transformer' => function(CraftCms\Cms\Entry\Elements\Entry $entry) {
            return [
                'title' => $entry->title,
                'url' => $entry->url,
                'jsonUrl' => Url::url("ingredients/$entry->slug.json"),
            ];
        },
        'pretty' => true,
    ];
},
```

```json5
{
    "data": [
        {
            "title": "Gin",
            "url": "/ingredients/gin",
            "jsonUrl": "/ingredients/gin.json"
        },
        {
            "title": "Tonic Water",
            "url": "/ingredients/tonic-water",
            "jsonUrl": "/ingredients/tonic-water.json"
        },
        // ...
    ],
    "meta": {
        "pagination": {
            "total": 66,
            "count": 10,
            "per_page": 10,
            "current_page": 1,
            "total_pages": 7,
            "links": {
                "next": "/ingredients.json?page=2"
            }
        }
    }
}
```

### Single Entry Endpoint

```php
'ingredients/{slug}.json' => function($slug) {
    return [
        'criteria' => [
            'section' => 'ingredients',
            'slug' => $slug
        ],
        'one' => true,
        'transformer' => function(CraftCms\Cms\Entry\Elements\Entry $entry) {
            // Create an array of all the photo URLs
            $photos = [];
            foreach ($entry->photos->all() as $photo) {
                $photos[] = $photo->url;
            }

            return [
                'title' => $entry->title,
                'url' => $entry->url,
                'description' => (string)$entry->description,
                'photos' => $photos
            ];
        },
        'pretty' => true,
    ];
},
```

```json5
{
    "title": "Gin",
    "url": "/ingredients/gin",
    "description": "<p>Gin is a spirit which derives its predominant flavour from juniper berries.</p>",
    "photos": [
        "/images/drinks/GinAndTonic1.jpg"
    ]
}
```

### JSON Feed

Here’s how to set up a [JSON Feed](https://jsonfeed.org/) ([Version 1.1](https://www.jsonfeed.org/version/1.1/)) for your site with Element API.

Note that `photos`, `body`, `summary`, and `tags` are imaginary custom fields.

```php
'feed.json' => function() {
    return [
        'serializer' => 'jsonFeed',
        'elementType' => CraftCms\Cms\Entry\Elements\Entry::class,
        'criteria' => ['section' => 'news'],
        'transformer' => function(CraftCms\Cms\Entry\Elements\Entry $entry) {
            $image = $entry->photos->one();
    
            return [
                'id' => (string)$entry->id,
                'url' => $entry->url,
                'title' => $entry->title,
                'content_html' => (string)$entry->body,
                'summary' => $entry->summary,
                'image' => $image ? $image->url : null,
                'date_published' => $entry->postDate->format(\DateTime::ATOM),
                'date_modified' => $entry->dateUpdated->format(\DateTime::ATOM),
                'authors' => [
                    ['name' => $entry->author->name],
                ],
                'language' => $entry->getSite()->language,
                'tags' => array_map('strval', $entry->tags->all()),
            ];
        },
        'meta' => [
            'description' => 'Recent news from Happy Lager',
        ],
        'pretty' => true,
    ];
},
```

```json5
{
    "version": "https://jsonfeed.org/version/1",
    "title": "Happy Lager",
    "home_page_url": "http://domain.com/",
    "feed_url": "http://domain.com/feed.json",
    "description": "Craft demo site",
    "items": [
        {
            "id": "24",
            "url": "http://domain.com/news/the-future-of-augmented-reality",
            "title": "The Future of Augmented Reality",
            "content_html": "<p>Nam libero tempore, cum soluta nobis est eligendi ...</p>",
            "date_published": "2016-05-07T00:00:00+00:00",
            "date_modified": "2016-06-03T17:43:36+00:00",
            "author": {
                "name": "Liz Murphy"
            },
            "tags": [
                "augmented reality",
                "futurism"
            ]
        },
        {
            "id": "4",
            "url": "http://domain.com/news/barrel-aged-digital-natives",
            "title": "Barrel Aged Digital Natives",
            "content_html": "<p>Nam libero tempore, cum soluta nobis est eligendi ...</p>",,
            "date_published": "2016-05-06T00:00:00+00:00",
            "date_modified": "2017-05-18T13:20:27+00:00",
            "author": {
                "name": "Liz Murphy"
            },
            "tags": [
                "barrel-aged"
            ]
        },
        // ...
    ]
}
```
