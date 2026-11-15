<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\BootsWithoutDatabase;

/**
 * Debug routes that read or rewrote real orders and purchases are gone. GET resend-email, which
 * the Lounge calls server to server, keeps its contract (200 resent, 404 not found) but is
 * throttled per IP and per email+event.
 */
class PublicDebugAndResendRoutesTest extends TestCase
{
    use BootsWithoutDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
        $this->app->forgetInstance('encrypter');

        // Every connection is an empty sqlite database, so nothing can reach MySQL.
        $sqlite = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''];
        config([
            'database.connections.events_test' => $sqlite,
            'database.connections.mysql_lounge' => $sqlite,
            'database.default' => 'events_test',
        ]);
        DB::purge('events_test');
        DB::purge('mysql_lounge');

        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('event_id');
            $table->string('email')->nullable();
            $table->string('first_name')->nullable();
            $table->string('order_reference')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('events', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->nullable();
            $table->string('club')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('Wo_Clickfunnel_Event_Purchase', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email_id')->nullable();
            $table->string('event_id', 200);
            $table->boolean('is_purchased')->default(true);
        });

        DB::table('events')->insert(['id' => 101, 'title' => 'Fixture night', 'club' => null]);
        DB::table('orders')->insert(['event_id' => 101, 'email' => 'buyer@example.test', 'order_reference' => 'FIXTURE1']);

        Mail::fake();
    }

    public function test_debug_routes_are_gone(): void
    {
        foreach (['/test', '/sample-receipt', '/email-test', '/email-test-agenda'] as $path) {
            $this->get($path)->assertStatus(404);
        }

        Mail::assertNothingSent();
        Mail::assertNothingQueued();
    }

    public function test_resend_email_keeps_its_contract(): void
    {
        $this->get('/resend-email?email=buyer@example.test&event_id=101')->assertOk();
        $this->get('/resend-email?email=nobody@example.test&event_id=101')->assertNotFound();
    }

    public function test_resend_email_is_limited_per_email_and_event(): void
    {
        foreach (range(1, 3) as $attempt) {
            $this->get('/resend-email?email=buyer@example.test&event_id=101')->assertOk();
        }

        $this->get('/resend-email?email=buyer@example.test&event_id=101')->assertStatus(429);
        // From another address, in other letter case: still the same buyer and event.
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.7'])
            ->get('/resend-email?email=BUYER@example.test&event_id=101')
            ->assertStatus(429);

        $sent = count(Mail::sent(\App\Mail\EventBooked::class)) + count(Mail::queued(\App\Mail\EventBooked::class));
        $this->assertSame(3, $sent);
    }

    public function test_resend_email_is_limited_per_ip(): void
    {
        foreach (range(1, 10) as $i) {
            $this->get("/resend-email?email=probe$i@example.test&event_id=101")->assertNotFound();
        }

        $this->get('/resend-email?email=probe11@example.test&event_id=101')->assertStatus(429);
        $this->get('/resend-email?email=buyer@example.test&event_id=101')->assertStatus(429);

        // Another address is not held up by this one.
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.7'])
            ->get('/resend-email?email=buyer@example.test&event_id=101')
            ->assertOk();
    }
}
