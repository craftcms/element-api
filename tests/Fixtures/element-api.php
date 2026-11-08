<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Facades\ResponseHeaders;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\ElementApi\Tests\Fixtures\NewsResource;
use League\Fractal\Resource\Item;

$news = fn (array $settings = []) => [
    'elementType' => Entry::class,
    'criteria' => ['section' => 'news', 'orderBy' => ['id' => SORT_ASC]],
    'transformer' => fn (Entry $entry) => ['id' => $entry->id, 'title' => $entry->title],
    'cache' => false,
    ...$settings,
];

$one = fn (int|string $entryId) => $news(['criteria' => ['id' => $entryId], 'one' => true]);

return [
    'endpoints' => [
        'news.json' => fn () => $news(['elementsPerPage' => 2]),
        'news/{entryId}.json' => fn (int $entryId) => $one($entryId),
        'legacy/<entryId:\d+>.json' => fn ($entryId) => $one($entryId),
        'legacy-slug/<slug:{slug}>.json' => fn (string $slug) => $news(['criteria' => ['slug' => $slug], 'one' => true]),
        'cached.json' => fn () => $news(['cache' => true, 'paginate' => false]),
        'jsonp.json' => fn () => $news(['callback' => 'handle', 'paginate' => false]),
        'feed.json' => fn () => $news(['serializer' => 'jsonFeed']),
        'cors.json' => function () use ($news) {
            ResponseHeaders::add('Access-Control-Allow-Origin', '*');

            return $news();
        },
        'site.json' => fn () => new Item(['handle' => Sites::getCurrentSite()->handle], fn (array $data) => $data),
        'resource-transformer.json' => fn () => $news(['transformer' => NewsResource::class, 'elementsPerPage' => 1, 'cache' => true]),
        'laravel-resource.json' => function () {
            ResponseHeaders::add('Access-Control-Allow-Origin', '*');

            return NewsResource::collection(Entry::find()->section('news')->orderBy('id')->paginate(1));
        },
        'laravel-resource/{entryId}.json' => fn (int $entryId) => new NewsResource(Entry::find()->id($entryId)->one() ?? abort(404)),
        'broken.json' => fn () => throw new RuntimeException('Secret internal detail'),
    ],
];
