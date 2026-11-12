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

// use App\Http\Endpoints\NewsEndpoint;
// use App\Http\Endpoints\NewsEntryEndpoint;

return [
    // Settings every endpoint starts from. Endpoints can override them.
    'defaults' => [
        // 'elementsPerPage' => 20,
    ],

    // The endpoints, keyed by URL pattern (Laravel route syntax, such as `news/{entryId}.json`).
    // Each value is an invokable class whose `__invoke()` method returns the endpoint’s settings.
    // (Functions work too, but Laravel can’t cache a config file that contains them.)
    'endpoints' => [
        // 'news.json' => NewsEndpoint::class,
        // 'news/{entryId}.json' => NewsEntryEndpoint::class,
    ],
];
