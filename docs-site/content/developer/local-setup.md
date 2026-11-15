---
title: Local setup
sidebar_position: 2
description: Get the Laravel 13 and Livewire 4 ticketing app running locally with seeded demo data and test coverage.
---

The application runs on PHP 8.3+ with MySQL 8 or SQLite. Node is only used to build the
Tailwind/Vite assets and the Docusaurus site, so a checkout without a frontend build will
render unstyled pages rather than fail.

## Requirements

| Tool | Version used | Notes |
| --- | --- | --- |
| PHP | 8.3+ (CI uses 8.4) | Extensions used in CI: `mbstring`, `pdo_sqlite`, `bcmath`, `intl`, `gd` |
| Composer | 2 | |
| Node / npm | Node 20+ | Builds Vite assets and `docs/` |
| MySQL | 8 | Or SQLite for a zero-setup start |

## First run

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Choose a database. The example `.env` points at MySQL:

```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=events_app
DB_USERNAME=root
DB_PASSWORD=
```

For SQLite, switch the connection and create the file first:

```bash
touch database/database.sqlite
```

```ini
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite
```

Then migrate, seed, link storage and build assets:

```bash
php artisan migrate --seed
php artisan storage:link
npm install
npm run build
php artisan serve
```

`composer setup` performs the install, `.env` copy, key generation, migration, `npm install
--ignore-scripts` and `npm run build` in one step, but it does not create the SQLite file.

## Seeded data

`database/seeders/DatabaseSeeder.php` creates:

| Record | Values |
| --- | --- |
| User | `demo@example.com` / `password` (name "Demo Organiser") |
| Organiser | "Demo Events Ltd" owned by that user |
| Event | "Demo Conference 2026", status `draft` |
| Tickets | "General admission" at `2500` minor units, "VIP" at `7500` minor units |

The event stays a draft until the organiser completes Stripe onboarding, because publishing
requires `Organiser::stripeReady()`. See [Stripe Connect](stripe-connect.md).

## Frontend assets

Vite builds `resources/css/app.css` and `resources/js/app.js`; the Blade layouts load them
with `@vite`. Tailwind 4 is configured in CSS (`@import 'tailwindcss'` and `@source` rules
in `resources/css/app.css`), so there is no `tailwind.config.js`.

```bash
npm run dev    # Vite dev server with hot reload
npm run build  # production assets into public/build
```

## Stripe without keys

Feature tests fake Stripe at the HTTP layer, so no keys or network access are
needed. For manual payment testing, set `STRIPE_KEY`, `STRIPE_SECRET` and
`STRIPE_WEBHOOK_SECRET`, then forward events:

```bash
stripe listen --forward-to localhost:8000/stripe/webhook
```

If `STRIPE_WEBHOOK_SECRET` is blank, `StripeWebhookController::parseEvent` parses the payload
without verifying a signature. That is a local convenience only; never leave it blank in a
deployed environment. See [payments and webhooks](payments-and-webhooks.md).

## Running the checks

```bash
php artisan test
vendor/bin/pint --test
npm run build
```

The suite uses SQLite `:memory:` and the array cache/session/queue, so it does not touch your
development database. Details are in [testing](testing.md).

## Documentation site

The docs are a separate npm project:

```bash
npm run docs:dev     # docusaurus start
npm run docs:build   # docusaurus build into docs/build
npm run docs:serve   # serve the built site
```

The same commands work as `npm --prefix docs run <script>`. See
[deployment and operations](deployment-and-operations.md).

## Common problems

| Symptom | Cause and fix |
| --- | --- |
| `No application encryption key has been specified` | Run `php artisan key:generate` |
| `could not find driver` on migrate | Install `pdo_sqlite`/`pdo_mysql`, or fix `DB_CONNECTION` |
| `Database file at path ... does not exist` | Create `database/database.sqlite` before migrating |
| Unstyled pages, missing `/build` assets | Run `npm run build` or `npm run dev` |
| `Vite manifest not found` | Run `npm run build`, or call `$this->withoutVite()` in the test |
| Publish button reports Stripe is not connected | Complete Express onboarding for the organiser first |
| Images 404 after upload | Run `php artisan storage:link` |
