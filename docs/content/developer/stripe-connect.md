---
title: Stripe Connect
sidebar_position: 8
description: Accounts v2 account creation, Express onboarding, capability readiness and requirement reporting.
---

Each organiser has one Stripe connected account. Accounts are created with the **Accounts v2**
API and use the Express dashboard. The platform creates both a `merchant` configuration
(card payments) and a `recipient` configuration (Stripe balance transfers), so the same
account can take charges and receive transfers.

`App\Services\Stripe\ConnectService` is the only place that talks to Connect. It receives the
shared `StripeClient` singleton by constructor injection.

## Account creation

`ConnectService::createAccount(Organiser $organiser)` is idempotent: if
`stripe_account_id` is already set it returns it without an API call. Otherwise it posts to
`/v2/core/accounts` with:

| Field | Value |
| --- | --- |
| `contact_email` | `organiser.email` |
| `display_name` | `organiser.name` |
| `dashboard` | `express` |
| `identity.country` | `strtolower(config('services.stripe.connect_country'))`, default `gb` |
| `configuration.merchant.capabilities.card_payments.requested` | `true` |
| `configuration.recipient.capabilities.stripe_balance.stripe_transfers.requested` | `true` |
| `defaults.responsibilities.fees_collector` | `application` |
| `defaults.responsibilities.losses_collector` | `application` |
| `metadata.organiser_id` | Organiser primary key as a string |
| `include` | `configuration.merchant`, `configuration.recipient`, `requirements`, `identity`, `defaults` |

The idempotency key is `organiser-account-{id}`. The returned account id is written to
`organisers.stripe_account_id` and the account is immediately passed to `applyAccount()`.

Both responsibility collectors must be `application` for an Express dashboard account: the
platform pays Stripe's processing fees and carries negative-balance liability. Switching to
a `full` dashboard requires both to be `stripe`, which is a code change, not configuration.

## The `include` parameter

Accounts v2 returns `configuration`, `requirements`, `identity` and `defaults` as `null`
unless they are explicitly requested. `ConnectService` therefore defines:

```php
private const INCLUDES = [
    'configuration.merchant',
    'configuration.recipient',
    'requirements',
    'identity',
    'defaults',
];
```

`createAccount()` sends it on creation and `retrieve()` sends it on every read. Removing it
silently breaks capability and requirement reporting.

## Onboarding

`ConnectService::onboardingUrl(Organiser $organiser)` creates the account if needed and posts
to `/v2/core/account_links`:

```php
'use_case' => [
    'type' => 'account_onboarding',
    'account_onboarding' => [
        'refresh_url' => route('stripe.connect.refresh', $organiser),
        'return_url' => route('stripe.connect.return', $organiser),
    ],
],
```

The two URLs are handled by `App\Http\Controllers\StripeConnectController`:

| Route | Action |
| --- | --- |
| `GET /stripe/connect/{organiser}/return` | Re-reads the account, redirects to the admin Stripe page with a status message |
| `GET /stripe/connect/{organiser}/refresh` | Redirects back with "Your onboarding link expired, please start again." |

Both call `abort_unless($request->user()?->can('manageStripe', $organiser), 403)`, so the
routes are safe even though they are registered in `routes/public.php`.

`App\Livewire\Admin\Organisers\StripeConnect::connect()` redirects the browser to
`onboardingUrl`, and `::dashboard()` redirects to `dashboardUrl`.

## Dashboard link

Accounts v2 has no login-link endpoint. `ConnectService::dashboardUrl` uses the v1
`accounts->createLoginLink($organiser->stripe_account_id)` call, because v1 login links accept
v2 account ids.

## Readiness

`ConnectService::applyAccount` writes three organiser columns:

| Column | Derivation |
| --- | --- |
| `stripe_charges_enabled` | `configuration.merchant.capabilities.card_payments.status === 'active'` |
| `stripe_details_submitted` | No `requirements.entries` with `awaiting_action_from = 'user'` and deadline `currently_due` or `past_due`; keeps the previous value when `requirements` is absent |
| `stripe_onboarded_at` | Set the first time both are true, cleared if either becomes false |

`Organiser::stripeReady()` is `stripe_account_id !== null && stripe_charges_enabled`. It gates:

- `EventPolicy::publish` and `Events\Form::publish`;
- `Checkout::continueToDetails`;
- `CheckoutService::createPaymentIntent`, which throws a validation error if not ready.

`stripe_details_submitted` is informational; readiness only depends on card payments being
active.

## Requirements reporting

`ConnectService::requirements(Organiser)` returns:

| Key | Contents |
| --- | --- |
| `currently_due` | Entries whose `minimum_deadline.status` is `currently_due` or `past_due` |
| `eventually_due` | Entries whose deadline status is `eventually_due` |
| `disabled_reason` | First `status_details` code on the card payments capability when it is not active |

Each entry is reduced to `description`, `awaiting_action_from` and an `errors` array of error
`code`s. The admin component loads this in `mount()` and after every refresh; API failures are
reported and replaced with empty arrays so the page still renders.

## Persisted state

| Column | Written by |
| --- | --- |
| `stripe_account_id` | `createAccount`, before onboarding |
| `stripe_charges_enabled` | `applyAccount` |
| `stripe_details_submitted` | `applyAccount` |
| `stripe_onboarded_at` | `applyAccount` |

Nothing else on the organiser is written by Connect. Charges are not enabled by the app;
Stripe enables the capability after onboarding and `account.updated` triggers a re-read (see
[payments and webhooks](payments-and-webhooks.md)).

## Tests

`tests/Feature/StripeConnectTest.php` covers account creation and idempotency, v2 account
links, capability/requirement syncing, the v1 login link, the missing-requirements case,
requirement bucketing, the admin page and publish gating. It uses
`Tests\Support\FakeStripeHttpClient`; no network or keys are required.

For organiser-facing instructions, see [Stripe Connect](../user/stripe-connect.md).
