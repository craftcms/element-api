<?php

declare(strict_types=1);

namespace CraftCms\ElementApi;

use CraftCms\Cms\Plugin\PluginSettings;

/**
 * Element API settings. These come from the project's `config/craft/element-api.php` file.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 2.0
 */
class Settings extends PluginSettings
{
    /**
     * @var array<string, mixed>|callable The default configuration for each endpoint
     */
    public mixed $defaults = [];

    /**
     * @var array<string, array<string, mixed>|callable|class-string> The endpoints, indexed by Laravel route pattern (e.g. `news/{entryId}`). Each one is a settings array, an invokable class name, or a function.
     */
    public array $endpoints = [];

    /**
     * Returns the default endpoint configuration.
     *
     * @return array<string, mixed>
     */
    public function getDefaults(): array
    {
        return is_callable($this->defaults) ? call_user_func($this->defaults) : $this->defaults;
    }

    /**
     * Returns the configuration for an endpoint.
     *
     * @return array<string, mixed>|callable|class-string|null
     */
    public function getEndpoint(string $pattern): array|callable|string|null
    {
        return $this->endpoints[$pattern] ?? null;
    }
}
