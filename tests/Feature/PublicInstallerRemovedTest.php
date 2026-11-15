<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\BootsWithoutDatabase;

/**
 * The installer inherited from Attendize answered at /install, gated only by an untracked
 * 'installed' file in the project root. The site was installed in 2024 and the installer could
 * not run any more: with no such file the page threw, and the POST threw at $this->validate(),
 * which the base Controller no longer has. Past that it read a VERSION file this repository no
 * longer has. With both back it rewrites .env and runs migrate, so the routes are removed rather
 * than left behind those two accidents.
 *
 * Each request runs through the real routes and the real exception handler, signed out. No
 * test posts database settings: with no route the body makes no difference, and test=db never
 * reaches the write, so a run against the old code stays harmless.
 */
class PublicInstallerRemovedTest extends TestCase
{
    use BootsWithoutDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
        $this->app->forgetInstance('encrypter');
        config(['app.debug' => false]);

        // One test creates this file and deletes it. A checkout with a real one stops here.
        $this->assertFileDoesNotExist(base_path('installed'));
    }

    public function test_get_install_is_a_404_with_no_installed_file(): void
    {
        $this->assertNoRoute($this->get('/install'));
    }

    public function test_get_install_is_a_404_on_an_installed_site(): void
    {
        touch(base_path('installed'));

        try {
            $response = $this->get('/install');
        } finally {
            unlink(base_path('installed'));
        }

        $this->assertNoRoute($response);
    }

    public function test_post_install_is_a_404_with_no_installed_file(): void
    {
        $this->assertNoRoute($this->post('/install', ['test' => 'db']));
    }

    public function test_no_route_reaches_the_installer(): void
    {
        $this->assertTrue(Route::has('logout'), 'The group that held the installer routes must still load.');

        $this->assertFalse(Route::has('showInstaller'));
        $this->assertFalse(Route::has('postInstaller'));

        $uris = array_map(fn ($route) => $route->uri(), Route::getRoutes()->getRoutes());

        $this->assertSame([], array_values(preg_grep('#(^|/)install(/|$)#', $uris)));
    }

    private function assertNoRoute(TestResponse $response): void
    {
        $response->assertNotFound();

        // Only the router says this. abort(404) in a controller carries no message.
        $this->assertStringContainsString('could not be found', $response->exception->getMessage());
    }
}
