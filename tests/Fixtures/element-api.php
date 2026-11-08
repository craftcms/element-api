<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Facades\ResponseHeaders;

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
        'cached.json' => fn () => $news(['cache' => true, 'paginate' => false]),
        'jsonp.json' => fn () => $news(['callback' => 'handle', 'paginate' => false]),
        'feed.json' => fn () => $news(['serializer' => 'jsonFeed']),
        'cors.json' => function () use ($news) {
            ResponseHeaders::add('Access-Control-Allow-Origin', '*');

            return $news();
        },
        'broken.json' => fn () => throw new RuntimeException('Secret internal detail'),
    ],
];
