<?php

declare(strict_types=1);

namespace CraftCms\ElementApi\Http\Controllers;

use CraftCms\Cms\Http\ResponseHeaders;
use CraftCms\Cms\Support\Arr;
use CraftCms\ElementApi\Http\EndpointResponse;
use CraftCms\ElementApi\Http\EndpointRoutes;
use CraftCms\ElementApi\Settings;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

/**
 * Serves an Element API endpoint.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 2.0
 */
class ElementApiController
{
    public function __construct(
        private readonly Settings $settings,
        private readonly ResponseHeaders $responseHeaders,
    ) {}

    public function __invoke(Request $request): SymfonyResponse
    {
        try {
            $pattern = $request->route(EndpointRoutes::ENDPOINT);
            $config = $this->settings->getEndpoint($pattern);

            if (is_callable($config)) {
                // Pass the route parameters to the closure by name
                $params = Arr::except($request->route()->parametersWithoutNulls(), EndpointRoutes::ENDPOINT);
                $config = app()->call($config, $params);
            }

            if ($request->isMethod('OPTIONS')) {
                // Now that the endpoint has had a chance to add CORS response headers, end the request
                return new Response('', 200);
            }

            // Prevent API endpoints from getting indexed (unless the endpoint set its own header)
            if (! $this->hasResponseHeader('X-Robots-Tag')) {
                $this->responseHeaders->add('X-Robots-Tag', 'none');
            }

            // Laravel API resources (and other responsables) build their own response
            $response = $config instanceof Responsable
                ? $config
                : app(EndpointResponse::class, ['config' => $config]);

            return $response->toResponse($request);
        } catch (Throwable $e) {
            return EndpointResponse::forException($e);
        }
    }

    private function hasResponseHeader(string $name): bool
    {
        return array_any(
            $this->responseHeaders->headers,
            fn (array $header) => strcasecmp($header['header'], $name) === 0,
        );
    }
}
