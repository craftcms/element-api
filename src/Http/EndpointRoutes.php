<?php

declare(strict_types=1);

namespace CraftCms\ElementApi\Http;

use Closure;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Validation\Rules\HandleRule;
use CraftCms\ElementApi\Http\Controllers\ElementApiController;
use CraftCms\ElementApi\Settings;
use Illuminate\Routing\Router;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Registers a route for each endpoint in `config/craft/element-api.php`.
 *
 * @since 5.0.0
 */
readonly class EndpointRoutes
{
    /**
     * The route default that holds the endpoint’s config key.
     */
    public const string ENDPOINT = 'elementApiEndpoint';

    public function __construct(
        private Settings $settings,
        private Sites $sites,
        private Router $router,
    ) {}

    public function register(): void
    {
        $sitePaths = $this->sitePaths();

        foreach ($this->settings->endpoints as $pattern => $config) {
            [$uri, $wheres] = $this->parsePattern((string) $pattern);
            $wheres += $this->numericParameters($uri, $config);

            foreach ($sitePaths as $sitePath) {
                $this->router->match(['GET', 'HEAD', 'OPTIONS'], ltrim("$sitePath/$uri", '/'), ElementApiController::class)
                    ->defaults(self::ENDPOINT, $pattern)
                    ->where($wheres);
            }
        }
    }

    /**
     * Returns the URI prefixes endpoints are served under: none, plus each site’s base URL path,
     * so `de/news.json` serves `news.json` for a site whose base URL is `https://example.com/de/`.
     *
     * @return list<string>
     */
    private function sitePaths(): array
    {
        if (! Cms::isInstalled()) {
            return [''];
        }

        return $this->sites->getAllSites()
            ->map(fn (Site $site): string => trim((string) parse_url((string) $site->getBaseUrl(), PHP_URL_PATH), '/'))
            ->prepend('')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Converts a Craft 5 (Yii) URL rule pattern, such as `news/<entryId:\d+>.json`, to Laravel route
     * syntax (`news/{entryId}.json`) and the parameters’ regular expressions. Laravel patterns are returned as-is.
     *
     * @return array{string, array<string, string>}
     */
    private function parsePattern(string $pattern): array
    {
        $wheres = [];

        $uri = preg_replace_callback('/<([\w._-]+):?([^>]+)?>/', function (array $match) use (&$wheres): string {
            if (($match[2] ?? '') !== '') {
                $wheres[$match[1]] = strtr($match[2], $this->regexTokens());
            }

            return '{'.$match[1].'}';
        }, $pattern);

        return [$uri, $wheres];
    }

    /**
     * The `{handle}`, `{slug}` and `{uid}` tokens Craft 5 URL rules could use in their regular expressions.
     *
     * @return array<string, string>
     */
    private function regexTokens(): array
    {
        $slugChars = ['.', '_', '-'];
        $slugWordSeparator = Cms::config()->slugWordSeparator;

        if ($slugWordSeparator !== '/' && ! in_array($slugWordSeparator, $slugChars, true)) {
            $slugChars[] = $slugWordSeparator;
        }

        return [
            '{handle}' => sprintf('(?:%s)', HandleRule::$handlePattern),
            '{slug}' => sprintf('(?:[\p{L}\p{N}\p{M}%s]+)', preg_quote(implode('', $slugChars), '/')),
            '{uid}' => sprintf('(?:%s)', Str::uuidPattern()),
        ];
    }

    /**
     * Returns whether an endpoint is configured with the name of an invokable class, such as `NewsEndpoint::class`.
     */
    public static function isInvokableClass(mixed $config): bool
    {
        return is_string($config) && class_exists($config) && method_exists($config, '__invoke');
    }

    /**
     * A parameter that the endpoint function (or invokable class) type-hints as `int` only matches digits,
     * so `news/abc.json` is a 404 rather than an error.
     *
     * @return array<string, string>
     */
    private function numericParameters(string $uri, mixed $config): array
    {
        $function = match (true) {
            $config instanceof Closure => new ReflectionFunction($config),
            self::isInvokableClass($config) => new ReflectionMethod($config, '__invoke'),
            default => null,
        };

        if ($function === null) {
            return [];
        }

        $wheres = [];

        foreach ($function->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && $type->getName() === 'int' && str_contains($uri, '{'.$parameter->getName().'}')) {
                $wheres[$parameter->getName()] = '[0-9]+';
            }
        }

        return $wheres;
    }
}
