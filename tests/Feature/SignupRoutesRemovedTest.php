<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\BootsWithoutDatabase;

/**
 * The site's sign-up stopped working and its routes stayed. GET /signup threw Class "Utils" not
 * found, POST /signup threw on $this->validate(), which no controller here has, and that POST was
 * the only thing that mailed a confirmation link. Members sign in through the Lobby.
 *
 * The controller and its views stay because #248 routes to them, as with the api routes in #328.
 */
class SignupRoutesRemovedTest extends TestCase
{
    use BootsWithoutDatabase;

    private const CONFIRMATION_CODE = 'FIXTURECONFIRMCODE';

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
        $this->app->forgetInstance('encrypter');

        config([
            'app.debug' => false,
            'database.connections.events_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'database.default' => 'events_test',
        ]);
        DB::purge('events_test');

        Http::preventStrayRequests();

        Schema::create('accounts', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email');
            $table->string('confirmation_code')->nullable();
            $table->boolean('is_confirmed')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('accounts')->insert(['id' => 1]);
        DB::table('users')->insert(['id' => 1, 'email' => 'owner@example.test', 'confirmation_code' => self::CONFIRMATION_CODE]);
    }

    public static function signupPages(): array
    {
        return [
            'the page' => ['/signup'],
            'the first run link' => ['/signup?first_run=yup'],
        ];
    }

    #[DataProvider('signupPages')]
    public function test_the_signup_page_is_gone(string $page): void
    {
        $this->get($page)->assertNotFound();
    }

    public function test_posting_a_signup_finds_no_route(): void
    {
        $this->post('/signup', [
            'first_name' => 'Fixture',
            'email' => 'stranger@example.test',
            'password' => 'fixture-password',
            'password_confirmation' => 'fixture-password',
        ])->assertNotFound();
    }

    public function test_a_confirmation_link_confirms_nobody(): void
    {
        $this->get('/signup/confirm_email/'.self::CONFIRMATION_CODE)->assertNotFound();

        $user = DB::table('users')->find(1);
        $this->assertSame(self::CONFIRMATION_CODE, $user->confirmation_code);
        $this->assertSame(0, (int) $user->is_confirmed);
    }

    public function test_no_signup_route_is_left(): void
    {
        $this->assertFalse(Route::has('showSignup'));
        $this->assertFalse(Route::has('confirmEmail'));

        $uris = array_map(fn ($route) => $route->uri(), Route::getRoutes()->getRoutes());
        $this->assertSame([], array_values(preg_grep('#^signup#', $uris)));
    }

    public function test_the_routes_around_them_stay(): void
    {
        $this->assertTrue(Route::has('login'));
        $this->assertTrue(Route::has('postResetPassword'));

        $this->get('/login/forgot-password')
            ->assertOk()
            ->assertViewIs('Public.LoginAndRegister.ForgotPassword');
    }
}
