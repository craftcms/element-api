<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\ElementApi\Tests\TestCase;

uses(TestCase::class)->in('Feature');

/**
 * Creates a live entry in the `news` section, which the fixture endpoints query.
 *
 * @param  array<string, mixed>  $attributes
 */
function newsEntry(array $attributes = []): Entry
{
    $section = Section::query()->where('handle', 'news')->first()
        ?? Section::factory()->create(['handle' => 'news', 'name' => 'News']);

    return EntryModel::factory()->createElement([
        'sectionId' => $section->id,
        'title' => 'News story',
        ...$attributes,
    ]);
}
