<?php

declare(strict_types=1);

namespace CraftCms\ElementApi\Tests\Fixtures;

use CraftCms\Cms\Entry\Elements\Entry;

/**
 * An endpoint configured by class name, as a config file that can be cached would use.
 */
class NewsEndpoint
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(int $entryId): array
    {
        return [
            'elementType' => Entry::class,
            'criteria' => ['id' => $entryId],
            'one' => true,
            'cache' => false,
            'transformer' => NewsResource::class,
        ];
    }
}
