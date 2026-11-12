<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\ElementApi\Events\SendingData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

it('paginates elements and links to the other pages', function () {
    $entries = collect(range(1, 3))->map(fn (int $i) => newsEntry(['title' => "Story $i"]));

    $this->get('/news.json')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/json; charset=UTF-8')
        ->assertExactJson([
            'data' => [
                ['id' => $entries[0]->id, 'title' => 'Story 1'],
                ['id' => $entries[1]->id, 'title' => 'Story 2'],
            ],
            'meta' => ['pagination' => [
                'total' => 3,
                'count' => 2,
                'per_page' => 2,
                'current_page' => 1,
                'total_pages' => 2,
                'links' => ['next' => 'https://localhost/news.json?page=2'],
            ]],
        ]);

    $this->get('/news.json?page=2')
        ->assertOk()
        ->assertJsonPath('data', [['id' => $entries[2]->id, 'title' => 'Story 3']])
        ->assertJsonPath('meta.pagination.links', ['previous' => 'https://localhost/news.json?page=1']);

    // Pages below 1 are the first page
    $this->get('/news.json?page=0')
        ->assertOk()
        ->assertJsonPath('meta.pagination.current_page', 1)
        ->assertJsonPath('data.0.id', $entries[0]->id);
});

it('passes route parameters to the endpoint function', function () {
    $entry = newsEntry(['title' => 'Found me']);

    $this->get("/news/$entry->id.json")->assertOk()->assertExactJson(['id' => $entry->id, 'title' => 'Found me']);

    // `int` parameters only match digits, so this doesn't reach the endpoint function (which would be a 500)
    $this->get('/news/abc.json')->assertNotFound();
});

it('calls invokable endpoint classes with the route parameters', function () {
    $entry = newsEntry(['title' => 'Invoked']);

    $this->get("/invokable/$entry->id.json")
        ->assertOk()
        ->assertExactJson(['id' => $entry->id, 'headline' => 'Invoked', 'site' => "invokable/$entry->id.json"]);

    // `int` parameters of `__invoke()` only match digits too
    $this->get('/invokable/abc.json')->assertNotFound();
});

it('supports Craft 5 URL rule patterns', function () {
    $entry = newsEntry(['title' => 'Legacy', 'slug' => 'legacy-story']);

    $this->get("/legacy/$entry->id.json")->assertOk()->assertJsonPath('title', 'Legacy');
    $this->get('/legacy/abc.json')->assertNotFound();

    $this->get('/legacy-slug/legacy-story.json')->assertOk()->assertJsonPath('id', $entry->id);
    $this->get('/legacy-slug/not%20a%20slug.json')->assertNotFound();
});

it('serves endpoints under each site’s base URL path', function () {
    $this->get('/site.json')->assertOk()->assertExactJson(['handle' => 'default']);
    $this->get('/de/site.json')->assertOk()->assertExactJson(['handle' => 'de']);

    $this->get('/de/feed.json')
        ->assertOk()
        ->assertJsonPath('title', 'German')
        ->assertJsonPath('home_page_url', 'https://localhost/de/')
        ->assertJsonPath('feed_url', 'https://localhost/de/feed.json');
});

it('returns errors as JSON without leaking exception messages', function () {
    $this->get('/news/999999.json')
        ->assertNotFound()
        ->assertExactJson(['error' => ['code' => 404, 'message' => 'No element exists that matches the endpoint criteria']]);

    $response = $this->get('/broken.json')
        ->assertStatus(500)
        ->assertExactJson(['error' => ['code' => 0, 'message' => 'A server error occurred.']]);

    expect($response->getContent())->not->toContain('Secret');
});

it('rejects unknown criteria params and endpoint settings', function (string $uri) {
    newsEntry();

    $this->get($uri)
        ->assertStatus(500)
        ->assertExactJson(['error' => ['code' => 0, 'message' => 'A server error occurred.']]);
})->with(['/unknown-criteria.json', '/unknown-setting.json']);

it('sends no cookies and asks robots not to index responses', function () {
    newsEntry();

    $response = $this->get('/news.json')->assertOk()->assertHeader('X-Robots-Tag', 'none');

    expect($response->headers->getCookies())->toBe([])
        ->and($response->headers->has('Set-Cookie'))->toBeFalse();
});

it('answers preflight requests with the headers the endpoint sets', function () {
    $this->call('OPTIONS', '/cors.json')
        ->assertOk()
        ->assertHeader('Access-Control-Allow-Origin', '*')
        ->assertContent('');

    $this->get('/cors.json')->assertOk()->assertHeader('Access-Control-Allow-Origin', '*');
});

it('encodes JSONP and JSON Feed responses', function () {
    $entry = newsEntry(['title' => '<b>"Fish" & chips</b>']);

    // JSONP is HTML-safe, as in Craft 5
    $this->get('/jsonp.json')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/javascript; charset=UTF-8')
        ->assertContent('handle({"data":[{"id":'.$entry->id.',"title":"\u003Cb\u003E\u0022Fish\u0022 \u0026 chips\u003C\/b\u003E"}]});');

    $this->get('/feed.json')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/feed+json; charset=UTF-8')
        ->assertJsonPath('version', 'https://jsonfeed.org/version/1.1')
        ->assertJsonPath('items.0.title', '<b>"Fish" & chips</b>');
});

it('caches responses until a queried element changes', function () {
    $entry = newsEntry(['title' => 'Original']);

    $this->get('/cached.json')->assertJsonPath('data.0.title', 'Original');

    // Changing the database directly doesn't invalidate the cache
    DB::table('elements_sites')->where('elementId', $entry->id)->update(['title' => 'Changed in the database']);
    $this->get('/cached.json')->assertJsonPath('data.0.title', 'Original');

    // Saving the element does
    $entry->title = 'Saved';
    Elements::saveElement($entry);
    $this->get('/cached.json')->assertJsonPath('data.0.title', 'Saved');
});

it('dispatches SendingData before sending the response', function () {
    newsEntry(['title' => 'Sent']);
    $payloads = [];
    Event::listen(function (SendingData $event) use (&$payloads) {
        $payloads[] = $event->payload->toArray();
    });

    $this->get('/news.json')->assertOk();

    expect($payloads)->toHaveCount(1)
        ->and($payloads[0]['data'][0]['title'])->toBe('Sent');
});

it('uses a Laravel API resource class as the transformer', function () {
    $entries = collect(range(1, 2))->map(fn (int $i) => newsEntry(['title' => "Story $i"]));

    $this->get('/resource-transformer.json')
        ->assertOk()
        ->assertExactJson([
            'data' => [['id' => $entries[0]->id, 'headline' => 'Story 1', 'site' => 'resource-transformer.json']],
            'meta' => ['pagination' => [
                'total' => 2,
                'count' => 1,
                'per_page' => 1,
                'current_page' => 1,
                'total_pages' => 2,
                'links' => ['next' => 'https://localhost/resource-transformer.json?page=2'],
            ]],
        ]);

    // Endpoint settings such as caching still apply
    $entries[0]->title = 'Saved';
    Elements::saveElement($entries[0]);
    $this->get('/resource-transformer.json')->assertJsonPath('data.0.headline', 'Saved');
});

it('sends Laravel API resources that an endpoint returns', function () {
    $entries = collect(range(1, 2))->map(fn (int $i) => newsEntry(['title' => "Story $i"]));

    $response = $this->get('/laravel-resource.json?page=2')
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'none')
        ->assertHeader('Access-Control-Allow-Origin', '*')
        ->assertJsonPath('data', [['id' => $entries[1]->id, 'headline' => 'Story 2', 'site' => 'laravel-resource.json']])
        ->assertJsonPath('meta.current_page', 2)
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('links.prev', 'http://localhost/laravel-resource.json?page=1');

    expect($response->headers->getCookies())->toBe([]);

    $this->get("/laravel-resource/{$entries[0]->id}.json")
        ->assertOk()
        ->assertExactJson(['data' => ['id' => $entries[0]->id, 'headline' => 'Story 1', 'site' => "laravel-resource/{$entries[0]->id}.json"]]);

    $this->get('/laravel-resource/999999.json')
        ->assertNotFound()
        ->assertJsonPath('error.code', 404);
});

it('generates image transforms before responding', function () {
    Queue::fake();
    config()->set('filesystems.disks.uploads', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/element-api-transforms'),
        'url' => 'https://localhost/uploads',
    ]);
    Storage::disk('uploads')->deleteDirectory('');
    $volume = Volume::factory()->create(['fs' => 'uploads', 'hasUrls' => true]);
    $asset = Asset::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => VolumeFolder::factory()->create(['volumeId' => $volume->id])->id,
        'filename' => 'photo.jpg',
        'width' => 40,
        'height' => 20,
        'dateModified' => now()->subMinute(),
    ]);
    ob_start();
    imagejpeg(imagecreatetruecolor(40, 20));
    Storage::disk('uploads')->put($asset->getPath(), ob_get_clean());

    // The final URL of a generated file, not a temporary `generate-transform` URL that would get cached
    $url = $this->get("/transform/$asset->id.json")->assertOk()->json('url');

    expect($url)->toStartWith('https://localhost/uploads/')
        ->and(Storage::disk('uploads')->exists(strtok(substr($url, strlen('https://localhost/uploads/')), '?')))->toBeTrue();
});
