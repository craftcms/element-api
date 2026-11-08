<?php

declare(strict_types=1);

namespace CraftCms\ElementApi\Tests;

use CraftCms\Cms\Plugin\Testing\PluginTestCase;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

abstract class TestCase extends PluginTestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        // Use Craft’s model factories, as core’s tests do
        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'CraftCms\\Cms\\Database\\Factories\\'.class_basename($modelName).'Factory',
        );
    }

    #[Override]
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('craft.element-api', require __DIR__.'/Fixtures/element-api.php');
    }
}
