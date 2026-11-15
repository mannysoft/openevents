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

The seeder creates `demo@example.com` / `password` with a demo organiser, event and two tickets.

## Stripe Connect

Payments run as **destination charges**: money is charged on the platform account and transferred to the organiser's connected account, with `application_fee_amount` retained by the platform.

1. Set the platform keys in `.env`:

   ```env
   STRIPE_KEY=pk_...
   STRIPE_SECRET=sk_...
   STRIPE_WEBHOOK_SECRET=whsec_...
   STRIPE_PLATFORM_FEE_PERCENT=5
   ```

2. Forward webhooks locally:

   ```bash
   stripe listen --forward-to localhost:8000/stripe/webhook
   ```

3. In the dashboard, open an organiser → **Stripe** and complete Express onboarding. Events cannot be published, and checkout stays disabled, until the connected account has `charges_enabled`.

Handled webhook events: `payment_intent.succeeded`, `payment_intent.payment_failed`, `payment_intent.canceled`, `charge.refunded`, `account.updated`.

Refunds are sent with `reverse_transfer` and `refund_application_fee`, so the platform fee is returned proportionally. All money is stored as integer minor units.

## Importing the legacy Attendize data

The previous Attendize database can be imported once, read-only, into this app:

```bash
php artisan import:legacy --dry-run
php artisan import:legacy --only=organisers,events,tickets
php artisan import:legacy
```

Set `DB_DATABASE_LEGACY` (and `DB_DATABASE_LEGACY_LOUNGE` for Stripe account ids that lived on the old `clubs` table) first. Imports are idempotent and keyed on `legacy_id`.

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
