<?php

declare(strict_types=1);

namespace CraftCms\ElementApi\Tests\Fixtures;

use CraftCms\Cms\Entry\Elements\Entry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin Entry
 */
class NewsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'headline' => $this->title,
            'site' => $request->path(),
        ];
    }
}
