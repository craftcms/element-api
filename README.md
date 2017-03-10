Element API for Craft CMS
=========================

This plugin makes it easy to create a JSON API for your entries (and other element types) in [Craft CMS](http://buildwithcraft.com).

It’s powered by Phil Sturgeon’s excellent [Fractal](http://fractal.thephpleague.com/) package.


## Requirements

This plugin requires Craft CMS 3.0.0-beta.1 or later.


## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require craftcms/element-api

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for Element API.

## Setup

To define your API endpoints, create a new `elementapi.php` file within your craft/config/ folder. This file should return an array with an `endpoints` key, which defines your site’s API endpoints. Within the `endpoints` array, keys are URL patterns, and values are endpoint configurations.

```php
<?php

use craft\elements\Entry;
use craft\helpers\UrlHelper;

return [
    'endpoints' => [
        'news.json' => [
            'elementType' => Entry::class,
            'criteria' => ['section' => 'news'],
            'transformer' => function(Entry $entry) {
                return [
                    'title' => $entry->title,
                    'url' => $entry->url,
                    'jsonUrl' => UrlHelper::url("news/{$entry->id}.json"),
                    'summary' => $entry->summary,
                ];
            },
        ],
        'news/<entryId:\d+>.json' => function($entryId) {
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

#### `elementType` _(Required)_

The class name of the element type that the API should be associated with. Craft’s built-in element type classes are:

- `craft\elements\Asset`
- `craft\elements\Category`
- `craft\elements\Entry`
- `craft\elements\GlobalSet`
- `craft\elements\MatrixBlock`
- `craft\elements\Tag`
- `craft\elements\User`

```php
'elementType' => craft\elements\Entry::class,
````

#### `criteria`

An array of parameters that should be set on the [ElementCriteriaModel](http://buildwithcraft.com/docs/templating/elementcriteriamodel) that will be fetching the elements.

```php
'criteria' => [
    'section' => 'news',
    'type' => 'article',
],
```

#### `transformer`

The [transformer](http://fractal.thephpleague.com/transformers/) that should be used to define the data that should be returned for each element. If you don’t set this, the default transformer will be used, which includes all of the element’s direct attribute values, but no custom field values.

```php
// Can be set to a function
'transformer' => function(craft\elements\Entry $entry) {
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

use craft\elements\Entry;
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

Note that it cannot be set to `'p'` because that’s the parameter Craft uses to check the requested path.


### Dynamic URL Patterns and Endpoint Configurations

URL patterns can contain dynamic subpatterns in the format of `<subpatternName:regex>`, where `subpatternName` is the name of the subpattern, and `regex` is a valid regular expression. For example, the URL pattern “`news/<entryId:\d+>.json`” will match URLs like `news/100.json`. You can also use the tokens `{handle}` and `{slug}` within your regular expression, which will be replaced with the appropriate regex patterns for matching handles and  element slugs.

Endpoint configurations can also be dynamic, by using a function instead of an array. If you do this, the function should return an array of configuration settings. Any subpattern matches in the URL pattern will be mapped to the function’s arguments. For example, if a URL pattern contains an `entryId` subpattern, and the endpoint configuration is a function with an `$entryId` argument, then whatever matches the URL subpattern will be passed to that function argument. This makes it easy to modify the resulting endpoint configuration based on the URL subpattern matches.

```php
'news/<entryId:\d+>.json' => function($entryId) {
    return [
        'elementType' => craft\elements\Entry::class,
        'criteria' => ['id' => $entryId],
        'one' => true,
    ];
},
```


### Setting Default Configuration Settings

You can specify default values for your endpoint configuration settings by adding a `defaults` key alongside your `endpoints` key (**not** within it).

```php
use craft\elements\Entry;

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
        'news.json' => [
            'criteria' => ['section' => 'news'],
        ],
        'news/<entryId:\d+>.json' => function($entryId) {
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
'ingredients.json' => [
    'criteria' => ['section' => 'ingredients'],
    'elementsPerPage' => 10,
    'transformer' => function(craft\elements\Entry $entry) {
        return [
            'title' => $entry->title,
            'url' => $entry->url,
            'jsonUrl' => UrlHelper::url("ingredients/{$entry->slug}.json"),
        ];
    },
],
```

```json5
{
    "data": [
        {
            "title": "Gin",
            "url": "\/ingredients\/gin",
            "jsonUrl": "\/ingredients\/gin.json"
        },
        {
            "title": "Tonic Water",
            "url": "\/ingredients\/tonic-water",
            "jsonUrl": "\/ingredients\/tonic-water.json"
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
                "next": "\/ingredients.json?p=2"
            }
        }
    }
}
```

### Single Entry Endpoint

```php
'ingredients/<slug:{slug}>.json' => function($slug) {
    return [
        'criteria' => [
            'section' => 'ingredients',
            'slug' => $slug
        ],
        'one' => true,
        'transformer' => function(craft\elements\Entry $entry) {
            // Create an array of all the photo URLs
            $photos = [];
            foreach ($entry->photos as $photo) {
                $photos[] = $photo->url;
            }

            return [
                'title' => $entry->title,
                'url' => $entry->url,
                'description' => (string) $entry->description,
                'photos' => $photos
            ];
        },
    ];
},
```

```json5
{
    "title": "Gin",
    "url": "\/ingredients\/gin",
    "description": "<p>Gin is a spirit which derives its predominant flavour from juniper berries.<\/p>",
    "photos": [
        "\/images\/drinks\/GinAndTonic1.jpg"
    ]
}
```
