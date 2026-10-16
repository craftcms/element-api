<?php

namespace CraftCms\ElementApi\Http\Controllers;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Http\ResponseHeaders;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Config;
use CraftCms\DependencyAwareCache\Dependency\TagDependency;
use CraftCms\DependencyAwareCache\Facades\DependencyCache;
use CraftCms\ElementApi\Events\SendingData;
use CraftCms\ElementApi\Resources\ResourceFactory;
use CraftCms\ElementApi\Serializers\JsonFeedV1Serializer;
use CraftCms\ElementApi\Settings;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use League\Fractal\Manager;
use League\Fractal\Serializer\ArraySerializer;
use League\Fractal\Serializer\DataArraySerializer;
use League\Fractal\Serializer\JsonApiSerializer;
use League\Fractal\Serializer\SerializerAbstract;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Serves an Element API endpoint.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 */
class ElementApiController
{
    public function __construct(
        private readonly Settings $settings,
        private readonly Sites $sites,
        private readonly ElementCaches $elementCaches,
        private readonly ResourceFactory $resources,
        private readonly ResponseHeaders $responseHeaders,
        private readonly GeneralConfig $generalConfig,
    ) {}

    public function __invoke(Request $request): Response
    {
        $callback = null;
        $jsonOptions = null;
        $pretty = false;
        $cache = true;
        $statusCode = 200;
        $statusText = null;
        $contentType = null;

        try {
            $pattern = $request->route('elementApiEndpoint');
            $config = $this->settings->getEndpoint($pattern);

            if (is_callable($config)) {
                // Pass the route parameters to the closure by name
                $params = Arr::except($request->route()->parametersWithoutNulls(), 'elementApiEndpoint');
                $config = app()->call($config, $params);
            }

            if ($request->isMethod('OPTIONS')) {
                // Now that the endpoint has had a chance to add CORS response headers, end the request
                return new Response('', 200);
            }

            if (is_array($config)) {
                // Merge in the defaults
                $config = Arr::merge($this->settings->getDefaults(), $config);
            }

            // Prevent API endpoints from getting indexed (unless the endpoint set its own header)
            if (!$this->hasResponseHeader('X-Robots-Tag')) {
                $this->responseHeaders->add('X-Robots-Tag', 'none');
            }

            // Before anything else, check the cache
            $cache = Arr::pull($config, 'cache', true);
            if ($request->isPreview()) {
                // Ignore config & disable cache for previews
                $cache = false;
            }

            $cacheKey = Arr::pull($config, 'cacheKey')
                ?? implode(':', [
                    'elementapi',
                    $this->sites->getCurrentSite()->id,
                    $request->path(),
                    $request->getQueryString(),
                ]);

            if ($cache) {
                $cached = DependencyCache::get($cacheKey);

                if (isset($cached['content'], $cached['contentType'])) {
                    return $this->respond($cached['content'], 200, null, $cached['contentType']);
                }

                $this->elementCaches->startCollectingCacheInfo();
            }

            // Extract config settings that aren't meant for the resource
            $serializer = Arr::pull($config, 'serializer');
            $callback = Arr::pull($config, 'callback');
            $jsonOptions = Arr::pull($config, 'jsonOptions', JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $pretty = Arr::pull($config, 'pretty', false);
            $includes = Arr::pull($config, 'includes', []);
            $excludes = Arr::pull($config, 'excludes', []);
            $contentType = Arr::pull($config, 'contentType');

            // Get the data resource
            $resource = $this->resources->create($config);

            // Load Fractal
            $fractal = new Manager();

            // Serialize the data
            if (!$serializer instanceof SerializerAbstract) {
                switch ($serializer) {
                    case 'dataArray':
                        $serializer = new DataArraySerializer();
                        break;
                    case 'jsonApi':
                        $serializer = new JsonApiSerializer();
                        break;
                    case 'jsonFeed':
                        $serializer = app(JsonFeedV1Serializer::class);
                        $contentType ??= 'application/feed+json';
                        break;
                    default:
                        $serializer = new ArraySerializer();
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

            // We were collecting cache info for a response that won't be cached
            if ($cache && $this->elementCaches->isCollectingCacheInfo()) {
                $this->elementCaches->stopCollectingCacheInfo();
            }

            // Log the exception
            Log::error('Error resolving Element API endpoint: ' . $e->getMessage(), ['exception' => $e]);
            report($e);
        }

        [$content, $responseContentType] = $this->encode($data, $callback, $jsonOptions, $pretty, $contentType);

        // Cache it?
        if ($statusCode !== 200) {
            $cache = false;
        }

        if ($cache) {
            // `true` uses the `cacheDuration` general config setting. As in Craft, 0 means "until invalidated"
            // (Laravel treats a 0 TTL as "don't cache", and null as "forever").
            $expire = Config::durationInSeconds($cache === true ? $this->generalConfig->cacheDuration : $cache) ?: null;

            [$dependency, $maxDuration] = $this->elementCaches->stopCollectingCacheInfo();
            $dependency ??= new TagDependency();
            $dependency->tags[] = 'element-api';

            if ($maxDuration) {
                $expire = $expire !== null ? min($expire, $maxDuration) : $maxDuration;
            }

            DependencyCache::put($cacheKey, [
                'content' => $content,
                'contentType' => $responseContentType,
            ], $expire, $dependency);
        }

        return $this->respond($content, $statusCode, $statusText, $responseContentType);
    }

    /**
     * Encodes the data as JSON (or JSONP), and returns the body and its content type.
     *
     * @param array<string, mixed> $data
     * @return array{string, string}
     */
    private function encode(array $data, ?string $callback, ?int $jsonOptions, bool $pretty, ?string $contentType): array
    {
        if ($callback !== null) {
            // JSONP responses use HTML-safe encoding, and ignore `jsonOptions` and `pretty`
            $json = $callback . '(' . json_encode(
                $data,
                JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_THROW_ON_ERROR,
            ) . ');';
        } else {
            $json = json_encode(
                $data,
                ($jsonOptions ?? 0) | ($pretty ? JSON_PRETTY_PRINT : 0) | JSON_THROW_ON_ERROR,
            );
        }

        $contentType = match (true) {
            $contentType === null => $callback !== null ? 'application/javascript; charset=UTF-8' : 'application/json; charset=UTF-8',
            !str_contains($contentType, 'charset') => "$contentType; charset=UTF-8",
            default => $contentType,
        };

        return [$json, $contentType];
    }

    private function respond(string $content, int $statusCode, ?string $statusText, string $contentType): Response
    {
        $response = new Response($content, $statusCode, ['Content-Type' => $contentType]);

        if ($statusText !== null) {
            $response->setStatusCode($statusCode, $statusText);
        }

        return $response;
    }

    private function hasResponseHeader(string $name): bool
    {
        return array_any(
            $this->responseHeaders->headers,
            fn(array $header) => strcasecmp($header['header'], $name) === 0,
        );
    }
}
