---
title: Configuration
sidebar_position: 3
description: Environment variables and config files that control the app, Stripe, currencies and persistence.
---

Configuration is ordinary Laravel configuration: environment variables feed files in
`config/`, and application code reads them with `config()`. Changes to `.env` take effect
immediately unless configuration has been cached with `php artisan config:cache`; run
`php artisan config:clear` in that case.

## Environment variables

These are the variables referenced by the application and its config files. `.env.example`
is the authoritative template.

### Application

| Variable | Default in `.env.example` | Used by |
| --- | --- | --- |
| `APP_NAME` | `"Events"` | Page titles, mail |
| `APP_ENV` | `local` | `config/app.php` |
| `APP_KEY` | empty | Encryption; generate with `php artisan key:generate` |
| `APP_DEBUG` | `true` | Error rendering |
| `APP_URL` | `http://localhost:8000` | URL generation, public disk URL |
| `APP_LOCALE` / `APP_FALLBACK_LOCALE` | `en` | `config/app.php` |
| `APP_FAKER_LOCALE` | `en_GB` | Factories and seeders |
| `LOG_CHANNEL` / `LOG_LEVEL` | `stack` / `debug` | `config/logging.php` |

`config/app.php` hard-codes `'timezone' => 'UTC'`. Event times are stored in UTC; the
organiser or event timezone is a domain value, not the framework timezone.

### Database and persistence

| Variable | Default | Notes |
| --- | --- | --- |
| `DB_CONNECTION` | `mysql` | `sqlite` also supported |
| `DB_HOST`, `DB_PORT` | `127.0.0.1`, `3306` | |
| `DB_DATABASE` | `events_app` | SQLite: path to the file |
| `DB_USERNAME`, `DB_PASSWORD` | `root`, empty | |
| `SESSION_DRIVER` | `database` | |
| `QUEUE_CONNECTION` | `database` | Queued mail and `SendAttendeeMessages` |
| `CACHE_STORE` | `database` | |
| `FILESYSTEM_DISK` | `local` | Uploads use the `public` disk explicitly |

### Mail

| Variable | Default | Notes |
| --- | --- | --- |
| `MAIL_MAILER` | `log` | `log` writes messages to the log; use `smtp` or a provider in production |
| `MAIL_HOST`, `MAIL_PORT` | `127.0.0.1`, `2525` | |
| `MAIL_USERNAME`, `MAIL_PASSWORD` | `null` | |
| `MAIL_FROM_ADDRESS` | `hello@example.com` | |
| `MAIL_FROM_NAME` | `${APP_NAME}` | |

See [mail and notifications](mail-and-notifications.md).

### Stripe

| Variable | Default | Read by |
| --- | --- | --- |
| `STRIPE_KEY` | empty | Publishable key, passed to Stripe.js in the checkout view |
| `STRIPE_SECRET` | empty | `StripeClient` singleton and all Stripe services |
| `STRIPE_WEBHOOK_SECRET` | empty | `StripeWebhookController::parseEvent`; blank means unverified |
| `STRIPE_PLATFORM_FEE_PERCENT` | `5` | Default application fee percentage |
| `STRIPE_CONNECT_COUNTRY` | `GB` | Lower-cased into Accounts v2 `identity.country` |

`config/services.php` exposes these under `services.stripe` as `key`, `secret`,
`webhook_secret`, `fee_percent` (cast to float) and `connect_country`.

`STRIPE_PLATFORM_FEE_PERCENT` is only the default. An organiser can override it with
`organisers.platform_fee_percent`; `Organiser::feePercent()` returns the override, falling
back to `config('services.stripe.fee_percent', 5)`.

### Events and currencies

| Variable | Default | Read by |
| --- | --- | --- |
| `EVENTS_CURRENCY` | `GBP` | `config/events.php` `default_currency` |
| `EVENTS_TIMEZONE` | `Europe/London` | `config/events.php` `default_timezone` |

`EVENTS_TIMEZONE` seeds the timezone field on new organisers and events; it does not change
the framework timezone or reinterpret stored timestamps.

### Frontend

| Variable | Default | Notes |
| --- | --- | --- |
| `VITE_APP_NAME` | `${APP_NAME}` | Available to Vite as `import.meta.env` |

## Config files

| File | Keys that matter |
| --- | --- |
| `config/events.php` | `default_currency`, `default_timezone`, `currencies` (symbol + decimals) |
| `config/services.php` | `stripe.key`, `stripe.secret`, `stripe.webhook_secret`, `stripe.fee_percent`, `stripe.connect_country` |
| `config/database.php` | Default `sqlite` when `DB_CONNECTION` is unset; MySQL and SQLite connections |
| `config/queue.php` | Default connection `database` |
| `config/cache.php` | Default store `database` |
| `config/session.php` | Driver from `SESSION_DRIVER` |
| `config/mail.php` | Default mailer from `MAIL_MAILER` |
| `config/filesystems.php` | `public` disk rooted at `storage/app/public`, URL `${APP_URL}/storage` |

## Where behaviour is configured, not hard-coded

- Currency metadata and the fallback currency: `config/events.php`. Adding a currency is
  covered in [extending](extending.md).
- The application fee default: `config/services.stripe.fee_percent`, overridden per
  organiser in the database.
- Connect country: `config/services.stripe.connect_country`.
- The platform/Express liability model is **not** configurable: `ConnectService` hard-codes
  `dashboard: express` and both responsibility collectors to `application`.
- The health endpoint is `/up`, registered in `bootstrap/app.php`; there is no separate
  configuration for it.
- No custom scheduler entries exist in `routes/console.php`.
