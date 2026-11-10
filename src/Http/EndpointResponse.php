<?php

declare(strict_types=1);

namespace CraftCms\ElementApi\Http;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Config;
use CraftCms\DependencyAwareCache\Dependency\TagDependency;
use CraftCms\DependencyAwareCache\Facades\DependencyCache;
use CraftCms\ElementApi\Contracts\ResourceAdapter;
use CraftCms\ElementApi\Events\SendingData;
use CraftCms\ElementApi\Resources\ResourceFactory;
use CraftCms\ElementApi\Serializers\JsonFeedV1Serializer;
use CraftCms\ElementApi\Settings;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use League\Fractal\Manager;
use League\Fractal\Resource\ResourceInterface;
use League\Fractal\Serializer\ArraySerializer;
use League\Fractal\Serializer\DataArraySerializer;
use League\Fractal\Serializer\JsonApiSerializer;
use League\Fractal\Serializer\SerializerAbstract;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Builds an endpoint’s response from its settings, a Fractal resource or a resource adapter, through
 * Fractal and the Element API cache.
 *
 * @internal
 *
 * @since 5.0.0
 */
readonly class EndpointResponse implements Responsable
{
    /**
     * @param  array<string, mixed>|ResourceInterface|ResourceAdapter  $config
     */
    public function __construct(
        private array|ResourceInterface|ResourceAdapter $config,
        private Settings $settings,
        private Sites $sites,
        private ElementCaches $elementCaches,
        private ResourceFactory $resources,
        private GeneralConfig $generalConfig,
    ) {}

    /**
     * @param  Request  $request
     */
    public function toResponse($request): Response
    {
        $config = $this->config;
        $callback = null;
        $jsonOptions = null;
        $pretty = false;
        $cache = true;
        $contentType = null;

        try {
            if (is_array($config)) {
                // Merge in the defaults
                $config = Arr::merge($this->settings->getDefaults(), $config);
            }

            // Endpoints can also return a resource or resource adapter, which have no settings to pull
            $pull = function (string $key, mixed $default = null) use (&$config): mixed {
                return is_array($config) ? Arr::pull($config, $key, $default) : $default;
            };

            // Before anything else, check the cache
            $cache = $pull('cache', true);
            if ($request->isPreview()) {
                // Ignore config & disable cache for previews
                $cache = false;
            }

            $cacheKey = $pull('cacheKey')
                ?? implode(':', [
                    'elementapi',
                    $this->sites->getCurrentSite()->id,
                    $this->sites->getRequestPath($request),
                    $request->getQueryString(),
                ]);

            if ($cache) {
                $cached = DependencyCache::get($cacheKey);

                if (isset($cached['content'], $cached['contentType'])) {
                    return self::respond($cached['content'], 200, null, $cached['contentType']);
                }

                $this->elementCaches->startCollectingCacheInfo();
            }

            // Extract config settings that aren't meant for the resource
            $serializer = $pull('serializer');
            $callback = $pull('callback');
            $jsonOptions = $pull('jsonOptions', JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $pretty = $pull('pretty', false);
            $includes = $pull('includes', []);
            $excludes = $pull('excludes', []);
            $contentType = $pull('contentType');

            // Get the data resource
            $resource = $this->resources->create($config);

            // Load Fractal
            $fractal = new Manager;

            // Serialize the data
            if (! $serializer instanceof SerializerAbstract) {
                switch ($serializer) {
                    case 'dataArray':
                        $serializer = new DataArraySerializer;
                        break;
                    case 'jsonApi':
                        $serializer = new JsonApiSerializer;
                        break;
                    case 'jsonFeed':
                        $serializer = app(JsonFeedV1Serializer::class);
                        $contentType ??= 'application/feed+json';
                        break;
                    default:
                        $serializer = new ArraySerializer;
                }
            }

            $fractal->setSerializer($serializer);

            // Parse includes/excludes
            $fractal->parseIncludes($includes);
            $fractal->parseExcludes($excludes);

            $data = $fractal->createData($resource);

            // Let listeners inspect the data before it's sent
            event(new SendingData($data));

            $data = $data->toArray();
        } catch (Throwable $e) {
            // We were collecting cache info for a response that won't be cached
            if ($cache && $this->elementCaches->isCollectingCacheInfo()) {
                $this->elementCaches->stopCollectingCacheInfo();
            }

            return self::forException($e, $callback, $jsonOptions, $pretty, $contentType);
        }

        [$content, $responseContentType] = self::encode($data, $callback, $jsonOptions, $pretty, $contentType);

        if ($cache) {
            // `true` uses the `cacheDuration` general config setting. As in Craft, 0 means "until invalidated"
            // (Laravel treats a 0 TTL as "don't cache", and null as "forever").
            $expire = Config::durationInSeconds($cache === true ? $this->generalConfig->cacheDuration : $cache) ?: null;

            [$dependency, $maxDuration] = $this->elementCaches->stopCollectingCacheInfo();
            $dependency ??= new TagDependency;
            $dependency->tags[] = 'element-api';

            if ($maxDuration) {
                $expire = $expire !== null ? min($expire, $maxDuration) : $maxDuration;
            }

            DependencyCache::put($cacheKey, [
                'content' => $content,
                'contentType' => $responseContentType,
            ], $expire, $dependency);
        }

        return self::respond($content, 200, null, $responseContentType);
    }

    /**
     * Logs the exception, and returns a JSON (or JSONP) error response that only reveals the messages of
     * HTTP exceptions.
     */
    public static function forException(
        Throwable $e,
        ?string $callback = null,
        ?int $jsonOptions = null,
        bool $pretty = false,
        ?string $contentType = null,
    ): Response {
        $isHttpException = $e instanceof HttpExceptionInterface;
        $statusCode = $isHttpException ? $e->getStatusCode() : 500;
        $message = $isHttpException
            ? ($e->getMessage() ?: (SymfonyResponse::$statusTexts[$statusCode] ?? 'Error'))
            : 'A server error occurred.';

        $data = [
            'error' => [
                'code' => $isHttpException ? $statusCode : $e->getCode(),
                'message' => $message,
            ],
        ];

        $statusText = $isHttpException
            ? (preg_split('/[\r\n]/', $message, 2)[0] ?: null)
            : 'Server error';

        Log::error('Error resolving Element API endpoint: '.$e->getMessage(), ['exception' => $e]);
        report($e);

        [$content, $responseContentType] = self::encode($data, $callback, $jsonOptions, $pretty, $contentType);

        return self::respond($content, $statusCode, $statusText, $responseContentType);
    }

    /**
     * Encodes the data as JSON (or JSONP), and returns the body and its content type.
     *
     * @param  array<string, mixed>  $data
     * @return array{string, string}
     */
    private static function encode(array $data, ?string $callback, ?int $jsonOptions, bool $pretty, ?string $contentType): array
    {
        if ($callback !== null) {
            // JSONP responses use HTML-safe encoding, and ignore `jsonOptions` and `pretty`
            $json = $callback.'('.json_encode(
                $data,
                JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_THROW_ON_ERROR,
            ).');';
        } else {
            $json = json_encode(
                $data,
                ($jsonOptions ?? 0) | ($pretty ? JSON_PRETTY_PRINT : 0) | JSON_THROW_ON_ERROR,
            );
        }

        $contentType = match (true) {
            $contentType === null => $callback !== null ? 'application/javascript; charset=UTF-8' : 'application/json; charset=UTF-8',
            ! str_contains($contentType, 'charset') => "$contentType; charset=UTF-8",
            default => $contentType,
        };

        return [$json, $contentType];
    }

    private static function respond(string $content, int $statusCode, ?string $statusText, string $contentType): Response
    {
        $response = new Response($content, $statusCode, ['Content-Type' => $contentType]);

        if ($statusText !== null) {
            $response->setStatusCode($statusCode, $statusText);
        }

        return $response;
    }
}
