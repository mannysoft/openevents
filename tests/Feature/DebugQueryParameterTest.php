<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Request;
use Tests\Concerns\BootsWithoutDatabase;

/**
 * A visitor must not be able to switch on debug mode (full error pages) or the Debugbar by adding
 * ?debug=1 to any URL.
 */
class DebugQueryParameterTest extends TestCase
{
    use BootsWithoutDatabase;

    public function test_debug_query_parameter_does_not_turn_on_debug_mode(): void
    {
        config(['app.debug' => false]);
        $this->app->instance('request', Request::create('/club/101?debug=1'));

        (new AppServiceProvider($this->app))->register();

        $this->assertFalse(config('app.debug'));
    }
}
