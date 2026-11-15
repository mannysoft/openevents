# Events

A small, deliberately bare multi-organiser ticketing platform built on **Laravel 13** and **Livewire 4**.

Organisers sign up, create events and tickets, and take payments through **their own Stripe account** using Stripe Connect (Express). The platform keeps a configurable application fee (5% by default) on each sale.

## Features

- Organiser accounts with multiple organisers per login
- Events with dates, venue, cover image, description and draft/live status
- Ticket types with price, quantity, sales window and per-order limits
- Public event pages and a Livewire checkout with Stripe Payment Element
- Discount codes, custom attendee questions, attendee messaging
- Orders, refunds, CSV/XLSX attendee exports and QR check-in
- Earnings report showing gross, platform fee and net per event
- Stripe Connect Express onboarding per organiser (`/dashboard/organisers/{organiser}/stripe`)

## Documentation

The user and developer guides live in [`docs-site/`](docs-site/README.md) and are built with
[Docusaurus](https://docusaurus.io). They are a separate npm project so they never
interfere with the Vite build above.

```bash
npm run docs:dev     # local dev server at http://localhost:3000/openevents/
npm run docs:build   # production build into docs-site/build
npm run docs:serve   # serve the built site
```

Published to GitHub Pages at <https://mannysoft.github.io/openevents/> by
[`.github/workflows/docs.yml`](.github/workflows/docs.yml). The repository's Pages source
must be set to **GitHub Actions** once.

## Requirements

- PHP 8.3+ (tested on 8.4)
- Composer 2
- Node 20+
- MySQL 8 (or SQLite for local development)

## Getting started

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
```

The seeder creates `demo@example.com` / `password` with a demo organiser, a draft event and two tickets. Publish the event once the organiser has connected Stripe.

## Stripe Connect

Payments run as **destination charges**: money is charged on the platform account and transferred to the organiser's connected account, with `application_fee_amount` retained by the platform.

Connected accounts are created with the **Accounts v2** API (`/v2/core/accounts`) and use the Express dashboard. Each organiser gets the `merchant` configuration (`card_payments`) and the `recipient` configuration (`stripe_balance.stripe_transfers`), so the same account can take charges and receive transfers. Because Stripe only allows an Express dashboard when the platform collects fees and carries loss liability, accounts are created with `defaults.responsibilities.fees_collector` and `losses_collector` set to `application` — the platform pays Stripe's processing fees and is liable for negative balances. Switch `dashboard` to `full` and set both responsibilities to `stripe` in `ConnectService` if Stripe should carry them instead.

1. Set the platform keys in `.env`:

   ```env
   STRIPE_KEY=pk_...
   STRIPE_SECRET=sk_...
   STRIPE_WEBHOOK_SECRET=whsec_...
   STRIPE_PLATFORM_FEE_PERCENT=5
   STRIPE_CONNECT_COUNTRY=GB
   ```

2. Forward webhooks locally:

   ```bash
   stripe listen \
  --events payment_intent.succeeded,payment_intent.payment_failed,payment_intent.canceled,charge.refunded,account.updated \
  --forward-to localhost:8000/stripe/webhook
   ```

3. In the dashboard, open an organiser → **Stripe** and complete Express onboarding. Until the connected account's `card_payments` capability is `active`, events can still be published and sold, but payments are taken on the platform account from `.env` and the organiser is settled outside Stripe.

Accounts v2 nulls out `configuration` and `requirements` unless they are requested, so every read passes `include`. Requirement entries are shown in the admin panel with their v2 description, deadline bucket (`currently_due`/`past_due` vs `eventually_due`) and any error codes.

Handled webhook events: `payment_intent.succeeded`, `payment_intent.payment_failed`, `payment_intent.canceled`, `charge.refunded`, `account.updated`. `account.updated` is still emitted for v2 accounts; the handler treats it as a signal and re-reads the account with the Accounts v2 API instead of trusting the v1 payload. Accounts v2 *thin* events are not subscribed to.

Refunds are sent with `reverse_transfer` and `refund_application_fee`, so the platform fee is returned proportionally. All money is stored as integer minor units.

## Testing

```bash
php artisan test
vendor/bin/pint --test
npm run build
```

Feature tests cover Stripe Connect onboarding, destination charges and the 5% application fee, webhook idempotency, refunds, order lifecycle, check-in, and the Livewire admin screens. Stripe is faked at the HTTP layer (`tests/Support/FakeStripeHttpClient.php`), so no network or keys are needed.

## Structure

- `app/Livewire/Admin` – dashboard, organisers, events, tickets, orders, attendees, check-in, discounts, questions, payouts, messaging
- `app/Livewire/Public` – event index, organiser page, event page, checkout, confirmation, ticket
- `app/Services` – order lifecycle and Stripe Connect/checkout/refund services
- `app/Models` – organiser, event, ticket, order, order item, attendee, discount code, question, answer, message log
