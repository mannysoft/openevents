---
title: Developer guide
sidebar_position: 1
description: Engineering guide to the Laravel 13 and Livewire 4 ticketing platform in this repository.
---

This guide is for engineers who maintain and extend the Events platform. It covers how the
application is assembled, how money and orders flow, how Stripe Connect and webhooks work,
and the conventions the codebase follows. It assumes you can read Laravel and Livewire code.

If you are looking for task-oriented instructions for organisers, see the
[user guide](../user/index.md).

## Stack

| Layer | Dependency | Version |
| --- | --- | --- |
| Language | PHP | `^8.3`, CI runs 8.4 |
| Framework | `laravel/framework` | `^13.17` |
| UI | `livewire/livewire` | `^4.0` |
| Payments | `stripe/stripe-php` | `^23.0` |
| PDF tickets | `barryvdh/laravel-dompdf` | `^3.0` |
| QR codes | `endroid/qr-code` | `^6.0` |
| Spreadsheets | `maatwebsite/excel` | `^3.1` |
| Assets | Vite 8, Tailwind 4, `laravel-vite-plugin` | see `package.json` |
| Tests | PHPUnit 12 | `phpunit.xml` |
| Formatting | Laravel Pint 1.27 | no `pint.json` |
| Docs | Docusaurus 3 | separate project in `docs-site/` |

## Repository map

| Path | Contents |
| --- | --- |
| `routes/` | Four route files loaded by `bootstrap/app.php` (`web`, `auth`, `admin`, `public`) plus `console.php` |
| `app/Livewire/Admin` | Organiser dashboard screens |
| `app/Livewire/Public` | Event browsing, checkout, confirmation, ticket |
| `app/Livewire/Auth` | Login, register, password reset |
| `app/Services` | `OrderService`, `ReferenceGenerator`, `Stripe/*` |
| `app/Support` | `Money` formatting and `Qr` generation |
| `app/Policies` | Four policies discovered by convention |
| `app/Http/Controllers` | Webhook, Connect return/refresh, exports, PDF, logout |
| `app/Models`, `app/Enums` | Domain model and backed enums |
| `database/migrations` | Schema; domain tables start at `2026_11_15_000001` |
| `resources/views` | Blade layouts, Livewire views, mail templates, PDF |
| `tests` | Feature tests plus the fake Stripe HTTP client |
| `docs` | This Docusaurus site (separate npm project) |

## Conventions used in this guide

- **Minor units.** Every monetary column stores an integer in the currency's smallest unit.
  `App\Support\Money` converts for display and input. See [money and currency](money-and-currency.md).
- **The platform** is this application and its Stripe platform account. **The connected
  account** is the Stripe Express account owned by an organiser.
- Paths such as `app/Services/OrderService.php` are relative to the repository root.
  `Class::method` refers to a real method on that class.
- There is no REST or JSON API. The application is server-rendered; all interactive
  behaviour is Livewire. See [architecture](architecture.md).

## Suggested reading order

1. [Local setup](local-setup.md) and [configuration](configuration.md) to get running.
2. [Architecture](architecture.md) and [domain model](domain-model.md) for the shape of the app.
3. [Money and currency](money-and-currency.md), [order lifecycle](order-lifecycle.md),
   [Stripe Connect](stripe-connect.md) and [payments and webhooks](payments-and-webhooks.md)
   for the parts that move money.
4. [Livewire components](livewire-components.md), [routes reference](routes-reference.md)
   and [authorization](authorization.md) for day-to-day changes.
5. [Testing](testing.md), [code style](code-style.md) and [deployment](deployment-and-operations.md)
   before shipping.
6. [Mail and notifications](mail-and-notifications.md) and [extending](extending.md) as needed.
