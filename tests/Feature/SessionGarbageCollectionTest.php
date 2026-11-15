<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Session\NullSessionHandler;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Tests\Concerns\BootsWithoutDatabase;

/**
 * Production keeps sessions in files with a lifetime of about 600 years, so a sweep walks all of
 * them and deletes none. It ran inside whichever web request won the lottery, and that request
 * could hit the 30 s limit, so no web request may sweep.
 */
class SessionGarbageCollectionTest extends TestCase
{
    use BootsWithoutDatabase;

    private const REQUESTS = 500;

    private object $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
        $this->app->forgetInstance('encrypter');

        $this->handler = new class extends NullSessionHandler
        {
            public int $sweeps = 0;

            public function gc($lifetime): int
            {
                $this->sweeps++;

                return 0;
            }
        };

        Session::extend('sweep_recorder', fn () => $this->handler);
        config(['session.driver' => 'sweep_recorder']);

        Route::middleware('web')->get('/session-sweep-probe', fn () => 'ok');
    }

    public function test_no_web_request_sweeps_the_sessions(): void
    {
        for ($i = 0; $i < self::REQUESTS; $i++) {
            $this->get('/session-sweep-probe')->assertOk();
        }

        $this->assertSame(0, $this->handler->sweeps);
    }

    public function test_the_lottery_cannot_be_won(): void
    {
        $this->assertSame(0, config('session.lottery')[0]);
    }

    /**
     * Shows the recorder sees a sweep, so the zero above means something.
     */
    public function test_a_won_lottery_reaches_the_recorder(): void
    {
        config(['session.lottery' => [100, 100]]);

        $this->get('/session-sweep-probe')->assertOk();

        $this->assertSame(1, $this->handler->sweeps);
    }
}
