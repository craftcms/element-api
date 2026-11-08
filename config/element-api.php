<?php

declare(strict_types=1);

/**
 * Element API endpoints.
 *
 * Publish this file to `config/craft/element-api.php` with
 * `php artisan vendor:publish --tag=element-api`.
 *
 * See https://github.com/craftcms/element-api/blob/5.x/README.md for every endpoint setting.
 */

// use CraftCms\Cms\Entry\Elements\Entry;

return [
    // Settings every endpoint starts from. Endpoints can override them.
    'defaults' => [
        // 'elementsPerPage' => 20,
    ],

    // The endpoints, keyed by URL pattern (Laravel route syntax, such as `news/{entryId}.json`).
    // Each value is a function that returns the endpoint’s settings.
    'endpoints' => [
        // 'news.json' => fn () => [
        //     'elementType' => Entry::class,
        //     'criteria' => ['section' => 'news'],
        //     'transformer' => fn (Entry $entry) => [
        //         'title' => $entry->title,
        //         'url' => $entry->url,
        //     ],
        // ],
        // 'news/{entryId}.json' => fn (int $entryId) => [
        //     'elementType' => Entry::class,
        //     'criteria' => ['id' => $entryId],
        //     'one' => true,
        // ],
    ],
];
