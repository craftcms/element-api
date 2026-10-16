<?php

namespace CraftCms\ElementApi\Resources;

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Typecast;
use CraftCms\ElementApi\Contracts\ResourceAdapter;
use Illuminate\Container\Attributes\Singleton;
use League\Fractal\Resource\ResourceInterface;

/**
 * Creates Fractal resources from endpoint configuration.
 */
#[Singleton]
class ResourceFactory
{
    /**
     * Creates a Fractal resource from an endpoint config array, a resource, or a resource adapter.
     *
     * @param array<string, mixed>|ResourceInterface|ResourceAdapter $config
     */
    public function create(array|ResourceInterface|ResourceAdapter $config): ResourceInterface
    {
        if ($config instanceof ResourceInterface) {
            return $config;
        }

        if ($config instanceof ResourceAdapter) {
            return $config->getResource();
        }

        // Default to ElementResource
        $class = Arr::pull($config, 'class', ElementResource::class);

        $resource = is_subclass_of($class, Component::class)
            ? new $class($config)
            : Typecast::configure(app($class), $config);

        if ($resource instanceof ResourceAdapter) {
            $resource = $resource->getResource();
        }

        return $resource;
    }
}
