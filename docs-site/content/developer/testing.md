---
title: Testing
sidebar_position: 13
description: The feature suite, the in-memory environment, the fake Stripe HTTP client and what each test file covers.
---

Tests are PHPUnit 12 tests run through `php artisan test`. Stripe is faked at the HTTP layer,
so the suite needs no keys, no network and no external services.

## Commands

```bash
php artisan test                      # all tests
php artisan test --filter=OrderServiceTest
php artisan test tests/Feature/StripeWebhookTest.php
vendor/bin/pint --test                # formatting check, as CI runs it
```

`composer test` clears the config cache and then runs `php artisan test`.

## Test environment

`phpunit.xml` forces these values, overriding `.env`:

| Variable | Value |
| --- | --- |
| `APP_ENV` | `testing` |
| `DB_CONNECTION` | `sqlite` |
| `DB_DATABASE` | `:memory:` |
| `CACHE_STORE` | `array` |
| `SESSION_DRIVER` | `array` |
| `QUEUE_CONNECTION` | `sync` |
| `MAIL_MAILER` | `array` |
| `BCRYPT_ROUNDS` | `4` |
| `BROADCAST_CONNECTION` | `null` |
| `PULSE_ENABLED`, `TELESCOPE_ENABLED`, `NIGHTWATCH_ENABLED` | `false` |

Feature tests use `Illuminate\Foundation\Testing\RefreshDatabase`, which migrates the
in-memory database once per test process and wraps each test in a transaction. Because the
queue is `sync`, queued mail and `SendAttendeeMessages` run inline during tests.

## Suite

`phpunit.xml` defines a single `Feature` suite covering `tests/Feature`.

## What is covered

| File | Focus |
| --- | --- |
| `Feature/OrderServiceTest` | Reservation with attendees, per-order limits, sold out, draft events, discounts, completion idempotency, cancellation, check-in rules |
| `Feature/CheckoutServiceTest` | Destination charge parameters, 5% fee, per-organiser fee override, refusal when not connected |
| `Feature/StripeWebhookTest` | Success idempotency, failure cancellation, refund sync, `account.updated` re-read and failure tolerance, unhandled types, single confirmation email |
| `Feature/StripeConnectTest` | v2 account creation/idempotency, account links, status and requirement sync, v1 login link, missing requirements, requirement buckets, admin page, publish policy, platform-account fallback |
| `Feature/RefundServiceTest` | Full refund with transfer reversal, partial clamping, no double refund, `syncRefunded` effects |
| `Feature/PublicPagesTest` | Event index visibility, event page, draft 404, organiser page, checkout reservation and intent, required details, discount, free orders, unready organiser, confirmation/ticket pages, unpaid tickets, Stripe failure |
| `Feature/AdminPagesTest` | Every admin page renders for the owner, other organisers are rejected, missing records 404 |
| `Feature/AttendeeExportTest` | CSV export, ownership, guest rejection |
| `Feature/AuthTest` | Login, invalid credentials, registration, unique email, logout, guest redirect from dashboard |

## Faking Stripe

`tests/Support/FakeStripeHttpClient.php` implements `Stripe\HttpClient\ClientInterface` and
is installed on the real SDK. `tests/TestCase.php` exposes it:

```php
protected function fakeStripe(): FakeStripeHttpClient
{
    $fake = new FakeStripeHttpClient;

    ApiRequestor::setHttpClient($fake);

    $this->beforeApplicationDestroyed(fn () => ApiRequestor::setHttpClient(null));

    return $fake;
}
```

The fake records every request in `$requests` and serves canned responses for:

| Path | Response |
| --- | --- |
| `POST /v2/core/accounts` | New `acct_fake{n}` with `configuration`, `requirements` and `defaults` |
| `GET /v2/core/accounts/{id}` | The stored account, or a 400 when `failAccountRetrieve` is set |
| `POST /v2/core/account_links` | Onboarding URL `https://connect.stripe.test/{account}` |
| `POST /v1/accounts/{id}/login_links` | Dashboard URL `https://dashboard.stripe.test/{id}` |
| `POST /v1/payment_intents` | `pi_fake{n}` with the submitted amount, fee, transfer data and metadata |
| `POST /v1/refunds` | `re_fake{n}` echoing amount, fee and transfer flags |

Knobs on the fake include `cardPaymentsStatus`, `stripeTransfersStatus`,
`cardPaymentsStatusDetails`, `requirementEntries`, `failAccountRetrieve` and
`omitRequirements`.

Typical usage:

```php
$stripe = $this->fakeStripe();

$intent = app(CheckoutService::class)->createPaymentIntent($order);

$this->assertSame('/v1/payment_intents', $stripe->requests[0]['path']);
$this->assertSame(125, $stripe->requests[0]['params']['application_fee_amount']);
```

Assert on `$stripe->requests[...]['params']` rather than on Stripe objects when you care about
what was sent.

## Writing a test

Use the factories in `database/factories` and their states:

```php
$organiser = Organiser::factory()->stripeReady()->create();
$event = Event::factory()->live()->create(['organiser_id' => $organiser->id]);
$ticket = Ticket::factory()->create(['event_id' => $event->id, 'price' => 2500]);
```

Useful states: `Organiser::factory()->stripeReady()`, `Event::factory()->live()`,
`Attendee::factory()->forTicket($ticket)`. `OrderFactory` defaults to `completed` with
`paid_at = now()`; override `status` when you need a reservation.

For Livewire behaviour use `Livewire::test(Component::class, [...])` and assertions such as
`assertSet`, `assertHasErrors`, `assertRedirect` and `assertSee`. `PublicPagesTest` has
working examples of checkout assertions.

For webhooks, build the payload array and post it:

```php
$this->postJson('/stripe/webhook', [
    'id' => 'evt_test_1',
    'type' => 'payment_intent.succeeded',
    'data' => ['object' => ['id' => 'pi_test', 'metadata' => ['order' => $order->reference]]],
])->assertOk();
```

With `MAIL_MAILER=array` use `Mail::assertQueued(OrderConfirmation::class)` to verify
notifications; `StripeWebhookTest` does exactly that.

## CI

`.github/workflows/laravel.yml` runs on pushes to `main` and `rewrite/livewire` and on every
pull request:

```yaml
- run: composer install --prefer-dist --no-interaction --no-progress
- run: cp .env.example .env && php artisan key:generate
- run: php artisan test
- run: vendor/bin/pint --test
```

PHP 8.4 with `mbstring`, `pdo_sqlite`, `bcmath`, `intl` and `gd`, no coverage. A formatting
failure fails the build just like a test failure. The docs have their own workflow; see
[deployment and operations](deployment-and-operations.md).
