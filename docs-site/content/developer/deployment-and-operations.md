---
title: Deployment and operations
sidebar_position: 17
description: Production environment, build and migrate steps, queue worker, Stripe webhook operations, backups and docs publishing.
---

The application is a standard Laravel deployment: a web server with `public/` as the document
root, a queue worker, and a database. There is no scheduler workload, because no schedule is
defined.

## Production environment

Set at least these values; everything else can keep the defaults in `config/`.

| Variable | Production value |
| --- | --- |
| `APP_NAME` | `"Events"` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | `base64:...`, stable and backed up |
| `APP_URL` | `https://tickets.example.com` |
| `DB_CONNECTION` | `mysql`, plus host, database and credentials |
| `SESSION_DRIVER`, `QUEUE_CONNECTION`, `CACHE_STORE` | `database` |
| `MAIL_MAILER` | `smtp`, plus host, port 587, username and password |
| `MAIL_FROM_ADDRESS` | `tickets@example.com` |
| `STRIPE_KEY`, `STRIPE_SECRET` | live keys |
| `STRIPE_WEBHOOK_SECRET` | endpoint signing secret |
| `STRIPE_PLATFORM_FEE_PERCENT` | `5` |
| `STRIPE_CONNECT_COUNTRY` | `GB` |
| `EVENTS_CURRENCY`, `EVENTS_TIMEZONE` | `GBP`, `Europe/London` |

`APP_KEY` must be stable: changing it invalidates encrypted cookies, sessions and any
encrypted values. Keep the previous key in `APP_PREVIOUS_KEYS` if you rotate.

## Build and release

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan storage:link
php artisan optimize
```

`--no-dev` excludes PHPUnit, Pint and Faker; the Vite build produces the `public/build`
manifest that `@vite` needs; `migrate --force` is required outside local and has no rollback
step; `storage:link` exposes `storage/app/public`; `optimize` caches config, routes, views and
events. Run these with the new code in place and the old worker stopped, or restart the worker
afterwards so it loads the cached config. Never run `migrate --seed` in production.

## Queue worker

Two features depend on a worker: queued mail (`OrderConfirmation`, `TicketIssued`) and
`App\Jobs\SendAttendeeMessages`. With the default `QUEUE_CONNECTION=database` the jobs land in
the `jobs` table.

```bash
php artisan queue:work --tries=3 --timeout=120
```

Supervise it with systemd, Supervisor or the platform's worker primitive. Without a worker,
buyers never receive confirmations, "Resend ticket" messages are never delivered, and bulk
messages stay reported as queued in `message_logs`. `SendAttendeeMessages` sends one message at
a time in a single job, so set a timeout that suits your largest list. Failed jobs land in
`failed_jobs`; inspect with `php artisan queue:failed` and retry with
`php artisan queue:retry all`.

## Scheduler

**There is no custom schedule.** `routes/console.php` registers only `inspire`,
`bootstrap/app.php` defines no `withSchedule`, and nothing else is scheduled, so this
deployment needs no cron entry. Discount expiry and sales windows are evaluated at request
time by `DiscountCode::isUsable()` and `Ticket::isOnSale()`. If you add scheduled work later,
add it to `routes/console.php` and configure `* * * * * php artisan schedule:run`.

## Stripe webhooks

Register `https://tickets.example.com/stripe/webhook` in the Stripe dashboard and subscribe to
`payment_intent.succeeded`, `payment_intent.payment_failed`, `payment_intent.canceled`,
`charge.refunded` and `account.updated`. Accounts v2 thin events are not used. The endpoint
must be reachable over HTTPS; the route is CSRF-exempt.

### Secret rotation

1. Roll the endpoint's signing secret in Stripe.
2. Update `STRIPE_WEBHOOK_SECRET`, run `php artisan config:clear` if config is cached, and
   restart workers and PHP-FPM.
3. Send a test event and confirm a `200` response.

A blank `STRIPE_WEBHOOK_SECRET` makes `StripeWebhookController::parseEvent` parse payloads
without verifying them. That behaviour exists for local development; in production it would
let anyone complete orders by posting JSON, so treat a blank secret as a misconfiguration.

### Local forwarding

```bash
stripe listen \
  --events payment_intent.succeeded,payment_intent.payment_failed,payment_intent.canceled,charge.refunded,account.updated \
  --forward-to localhost:8000/stripe/webhook
```

Copy the printed secret into `.env`. Replay individual events from the Stripe dashboard or
with the CLI; the handlers are idempotent for completion and refund totals.

### Delivery failures

The controller returns `200` for every successfully parsed event, including unhandled types,
so Stripe does not retry those. A `400` means the signature or JSON was invalid and Stripe
will retry. `account.updated` catches Stripe API errors, reports them and still returns `200`;
the status is eventually corrected by the next event or a manual refresh in the admin UI.

## Logs and monitoring

| Concern | Where |
| --- | --- |
| Application log | `storage/logs/laravel.log` (`LOG_CHANNEL`, `LOG_LEVEL`) |
| Local tailing | `php artisan pail` |
| Health check | `GET /up` |
| Failed jobs | `failed_jobs`, `php artisan queue:failed` |
| Stripe API errors | `report($e)` in Stripe components and the webhook |
| Webhook audit | Stripe dashboard only; no event log table |

There is no APM, Sentry, Pulse or Telescope wiring. Monitor `/up`, queue depth and Stripe's
webhook delivery page. Useful checks: `php artisan about`, `php artisan migrate:status`,
`php artisan queue:monitor default:100`.

## Backups

| Data | Method |
| --- | --- |
| MySQL database | Scheduled `mysqldump --single-transaction` or managed snapshots, stored off-host |
| Uploaded images | Back up `storage/app/public` |
| Application key | Keep `APP_KEY` in a secret manager, separately |
| Stripe state | Payment intents, charges and refunds stay authoritative in Stripe |

Without the database, tickets cannot be reissued. Test a restore into staging periodically.
Refund totals are monotonic, so an older snapshot can show a stale `refunded_amount` until
the next `charge.refunded` event, but it cannot corrupt Stripe.

## Documentation site

The Docusaurus site in `docs-site/` is a separate npm project. `npm run docs:dev`,
`npm run docs:build` and `npm run docs:serve` wrap
`npm --prefix docs-site run start|build|serve`; `npm --prefix docs-site run build` always
works. Node 20+ is required and `docs-site/package.json` pins Docusaurus 3.10.2.

`.github/workflows/docs.yml` ("Docs site") runs on changes under `docs-site/**` and on the
workflow itself. The build job runs `npm ci` and `npm run build` on Node 22 with `DOCS_URL`
and `DOCS_BASE_URL` set from the repository context, so forks publish to their own path.
Pull requests build only; pushes to `main` and manual dispatch upload `docs-site/build` as
the Pages artifact and a separate deploy job publishes it.

One-time setup: **Settings → Pages → Build and deployment → Source** must be
**GitHub Actions**, otherwise the deploy job has no target. `onBrokenLinks: 'throw'` and
`onBrokenAnchors: 'throw'` mean a broken internal link fails the docs build, so run
`npm run docs:build` before opening a documentation pull request.
