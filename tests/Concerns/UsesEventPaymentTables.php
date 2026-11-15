<?php

namespace Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Stripe\ApiRequestor;
use Tests\Support\FakeStripeHttpClient;

/**
 * In-memory sqlite stand-ins for the tables the payment code reads (events DB: orders, events;
 * Lounge DB: clubs), and a fake Stripe HTTP client so nothing reaches Stripe.
 */
trait UsesEventPaymentTables
{
    protected FakeStripeHttpClient $stripeHttp;

    protected function setUpEventPaymentTables(): void
    {
        $sqlite = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''];

        config([
            'database.connections.events_test' => $sqlite,
            'database.connections.mysql_lounge' => $sqlite,
            'database.default' => 'events_test',
            'services.stripe.secret' => 'sk_test_fixture_not_a_real_key',
        ]);

        DB::purge('events_test');
        DB::purge('mysql_lounge');

        Schema::connection('events_test')->create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('event_id');
            $table->string('email')->nullable();
            $table->string('order_reference')->nullable();
            $table->string('transaction_id')->nullable();
        });

        Schema::connection('events_test')->create('events', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->nullable();
            $table->string('club')->nullable();
            $table->dateTime('start_date')->nullable();
        });

        // The Lounge's purchase table, which lives in the events database. Columns as in
        // production (the Lounge's EventPurchaseFillableTest lists all 26); event_id is a
        // varchar there.
        Schema::connection('events_test')->create('Wo_Clickfunnel_Event_Purchase', function (Blueprint $table) {
            $table->increments('id');
            foreach ([
                'email_id', 'friend_email', 'referal_email', 'referal_first_name', 'referal_last_name',
                'full_name', 'first_name', 'last_name', 'mobile_no', 'club', 'type', 'zoom_url',
                'registrant_id', 'notification_status',
            ] as $column) {
                $table->string($column)->nullable();
            }
            $table->dateTime('created')->nullable();
            $table->string('event_id', 200);
            $table->decimal('amount', 10, 2)->default(0);
            $table->unsignedInteger('user_id')->nullable();
            $table->boolean('is_member')->default(false);
            $table->boolean('is_purchased')->default(true);
            $table->boolean('is_paid_points')->default(false);
            $table->integer('points_deducted')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('mysql_lounge')->create('clubs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug');
            $table->string('name')->nullable();
            $table->string('stripe_account_id')->nullable();
            $table->boolean('is_onboarded')->default(false);
            $table->integer('stripe_fee_percent')->nullable();
            $table->integer('stripe_fee_fixed')->nullable();
            $table->timestamps();
        });

        $this->stripeHttp = new FakeStripeHttpClient;
        ApiRequestor::setHttpClient($this->stripeHttp);
    }

    protected function tearDownEventPaymentTables(): void
    {
        ApiRequestor::setHttpClient(null);
    }

    /**
     * A row as the Lounge's PaymentController::storeTicket writes it (created_at set, `created`
     * empty, is_purchased left at its default of 1).
     */
    protected function addLoungePurchase(string $eventId, string $email, array $overrides = []): void
    {
        DB::table('Wo_Clickfunnel_Event_Purchase')->insert(array_replace([
            'event_id' => $eventId,
            'user_id' => 9001,
            'email_id' => $email,
            'full_name' => 'Lounge Member',
            'first_name' => 'Lounge',
            'last_name' => 'Member',
            'mobile_no' => '',
            'club' => '',
            'is_member' => 0,
            'amount' => 30,
            'created_at' => now()->subMinutes(55)->format('Y-m-d H:i:s'),
            'updated_at' => now()->subMinutes(55)->format('Y-m-d H:i:s'),
        ], $overrides));
    }

    protected function addClub(string $slug, ?string $account, bool $onboarded = true): void
    {
        DB::connection('mysql_lounge')->table('clubs')->insert([
            'slug' => $slug,
            'name' => ucfirst($slug),
            'stripe_account_id' => $account,
            'is_onboarded' => $onboarded,
            'stripe_fee_percent' => 290,
            'stripe_fee_fixed' => 30,
        ]);
    }
}
