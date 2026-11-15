<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Order;
use App\Models\Organiser;
use App\Services\Stripe\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_destination_charge_with_the_platform_fee(): void
    {
        config(['services.stripe.fee_percent' => 5]);

        $organiser = Organiser::factory()->stripeReady()->create();
        $event = Event::factory()->live()->create(['organiser_id' => $organiser->id]);
        $order = Order::factory()->create([
            'event_id' => $event->id,
            'organiser_id' => $organiser->id,
            'total' => 10000,
            'currency_code' => 'GBP',
        ]);

        $stripe = $this->fakeStripe();

        $intent = app(CheckoutService::class)->createPaymentIntent($order);

        $request = collect($stripe->requests)->firstWhere('path', '/v1/payment_intents');

        $this->assertNotNull($request);
        $this->assertSame(10000, $request['params']['amount']);
        $this->assertSame(500, $request['params']['application_fee_amount']);
        $this->assertSame($organiser->stripe_account_id, $request['params']['transfer_data']['destination']);
        $this->assertSame('gbp', $request['params']['currency']);
        $this->assertSame($order->reference, $request['params']['metadata']['order']);

        $order->refresh();
        $this->assertSame($intent->id, $order->stripe_payment_intent_id);
        $this->assertSame(500, $order->application_fee_amount);
        $this->assertSame($organiser->stripe_account_id, $order->stripe_account_id);
    }

    public function test_per_organiser_fee_override_is_used(): void
    {
        $organiser = Organiser::factory()->stripeReady()->create(['platform_fee_percent' => 10]);
        $event = Event::factory()->live()->create(['organiser_id' => $organiser->id]);
        $order = Order::factory()->create([
            'event_id' => $event->id,
            'organiser_id' => $organiser->id,
            'total' => 2000,
            'currency_code' => 'GBP',
        ]);

        $stripe = $this->fakeStripe();

        app(CheckoutService::class)->createPaymentIntent($order);

        $request = collect($stripe->requests)->firstWhere('path', '/v1/payment_intents');

        $this->assertSame(200, $request['params']['application_fee_amount']);
    }

    public function test_it_falls_back_to_the_platform_account_when_the_organiser_is_not_connected(): void
    {
        $organiser = Organiser::factory()->create();
        $event = Event::factory()->live()->create(['organiser_id' => $organiser->id]);
        $order = Order::factory()->create([
            'event_id' => $event->id,
            'organiser_id' => $organiser->id,
            'total' => 10000,
            'currency_code' => 'GBP',
        ]);

        $stripe = $this->fakeStripe();

        $intent = app(CheckoutService::class)->createPaymentIntent($order);

        $request = collect($stripe->requests)->firstWhere('path', '/v1/payment_intents');

        $this->assertSame(10000, $request['params']['amount']);
        $this->assertArrayNotHasKey('application_fee_amount', $request['params']);
        $this->assertArrayNotHasKey('transfer_data', $request['params']);

        $order->refresh();
        $this->assertSame($intent->id, $order->stripe_payment_intent_id);
        $this->assertNull($order->stripe_account_id);
        // Still recorded so the earnings report shows what the platform retains.
        $this->assertSame(500, $order->application_fee_amount);
    }

    public function test_it_falls_back_to_the_platform_account_when_the_connected_account_cannot_take_charges(): void
    {
        $organiser = Organiser::factory()->create([
            'stripe_account_id' => 'acct_pending',
            'stripe_charges_enabled' => false,
        ]);
        $event = Event::factory()->live()->create(['organiser_id' => $organiser->id]);
        $order = Order::factory()->create([
            'event_id' => $event->id,
            'organiser_id' => $organiser->id,
            'total' => 2000,
            'currency_code' => 'GBP',
        ]);

        $stripe = $this->fakeStripe();

        app(CheckoutService::class)->createPaymentIntent($order);

        $request = collect($stripe->requests)->firstWhere('path', '/v1/payment_intents');

        $this->assertArrayNotHasKey('transfer_data', $request['params']);
        $this->assertNull($order->fresh()->stripe_account_id);
    }
}
