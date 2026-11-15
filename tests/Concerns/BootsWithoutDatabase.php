<?php

namespace Tests\Concerns;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

/**
 * Boots the application for a test that needs no database.
 *
 * Tests\TestCase migrates a database in setUp() and uses a CreatesApplication trait this
 * repository no longer has, so a test built on it cannot run. Use this trait on a class that
 * extends Illuminate\Foundation\Testing\TestCase instead.
 *
 * routes/web.php calls clubs() while it registers routes, and clubs() queries mysql_lounge
 * unless 'cached_clubs_slugs' is cached. Seeding that key before the routes load lets the
 * application boot with no database connection at all.
 */
trait BootsWithoutDatabase
{
    public function createApplication()
    {
        $app = require Application::inferBasePath().'/bootstrap/app.php';

        $this->traitsUsedByTest = array_flip(class_uses_recursive(static::class));

        $app->booting(function () use ($app) {
            $app['cache']->put('cached_clubs_slugs', ['london'], 3600);
        });

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
