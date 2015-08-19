# Element API plugin for Craft

This plugin makes it easy to create a JSON API for your entries (and other element types) in [Craft](http://buildwithcraft.com).

It’s powered by Phil Sturgeon’s excellent [Fractal](http://fractal.thephpleague.com/) package.


## Requirements

Element API requires PHP 5.4 or later and Craft 2.1 or later.


## Installation

To install Element API, follow these steps:

1.  Upload the elementapi/ folder to your craft/plugins/ folder.
2.  Go to Settings > Plugins from your Craft control panel and enable the Element API plugin.


## Setup

To define your API endpoints, create a new `elementapi.php` file within your craft/config/ folder. This file should return an array with an `endpoints` key, which defines your site’s API endpoints. Within the `endpoints` array, keys are URL patterns, and values are endpoint configurations.

```php
<?php
namespace Craft;

return [
    'endpoints' => [
        'news.json' => [
            'elementType' => 'Entry',
            'criteria' => ['section' => 'news'],
            'transformer' => function(EntryModel $entry) {
                return [
                    'title' => $entry->title,
                    'url' => $entry->url,
                    'jsonUrl' => UrlHelper::getUrl("news/{$entry->id}.json"),
                    'summary' => $entry->summary,
                ];
            },
        ],
        'news/<entryId:\d+>.json' => function($entryId) {
            return [
                'elementType' => 'Entry',
                'criteria' => ['id' => $entryId],
                'first' => true,
                'transformer' => function(EntryModel $entry) {
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

The element type class name that the API should be associated with. Possible values are `Asset`, `Category`, `Entry`, `GlobalSet`, `MatrixBlock`, `Tag`, and `User`, as well as any plugin-based element type class names like `SproutForms_Entry`.

```php
'elementType' => 'Entry',
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
'transformer' => function(EntryModel $entry) {
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

Note that if you return a Transformer class configuration or instance, you will need to load the class yourself ahead of time.

```php
'entries.json' => function() {
    require craft()->path->getConfigPath().'EntryTransformer.php';

    return [
        'elementType' => 'Entry',
        'transformer' => 'EntryTransformer',
    ];
}
```

#### `first`

Whether only the _first_ matching element should be returned. This is set to `false` by default, meaning that _all_ matching elements will be returned.

```php
'first' => true,
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
        'elementType' => 'Entry',
        'criteria' => ['id' => $entryId],
        'first' => true,
    ];
},
```


### Setting Default Configuration Settings

You can specify default values for your endpoint configuration settings by adding a `defaults` key alongside your `endpoints` key (**not** within it).

```php
return [
    'defaults' => [
        'elementType' => 'Entry',
        'elementsPerPage' => 10,
        'pageParam' => 'pg',
        'transformer' => function(EntryModel $entry) {
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
                'first' => true,
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
    'transformer' => function(EntryModel $entry) {
        return [
            'title' => $entry->title,
            'url' => $entry->url,
            'jsonUrl' => UrlHelper::getUrl("ingredients/{$entry->slug}.json"),
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
        'first' => true,
        'transformer' => function(EntryModel $entry) {
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
