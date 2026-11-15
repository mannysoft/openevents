---
title: Routes reference
sidebar_position: 11
description: Every registered route, its name, handler, middleware and route model binding key.
---

Four route files are loaded in `bootstrap/app.php`: `routes/web.php`, `routes/auth.php`,
`routes/admin.php` and `routes/public.php`. All of them run in the `web` middleware group.
There is no `routes/api.php`.

## Public

| Method | URI | Name | Handler |
| --- | --- | --- | --- |
| GET | `/` | `home` | Redirect to `/events` |
| GET | `/events` | `events.index` | `Public\EventIndex` |
| GET | `/o/{organiser}` | `organisers.show` | `Public\OrganiserPage` |
| GET | `/e/{event}` | `events.show` | `Public\EventPage` |
| GET | `/e/{event}/checkout` | `checkout` | `Public\Checkout` |
| GET | `/orders/{order}` | `orders.confirmation` | `Public\OrderConfirmation` |
| GET | `/tickets/{attendee}` | `tickets.show` | `Public\Ticket` |
| GET | `/tickets/{attendee}/pdf` | `tickets.pdf` | `TicketPdfController::show` |

These routes have no authentication or policy checks. The unguessable reference in the URL
(`Attendee.reference`, `Order.reference`) is the access token for order, ticket and PDF
pages. Event, checkout and organiser pages additionally require the event to be live and
public.

## Authentication

| Method | URI | Name | Handler | Middleware |
| --- | --- | --- | --- | --- |
| GET | `/login` | `login` | `Auth\Login` | `guest` |
| GET | `/register` | `register` | `Auth\Register` | `guest` |
| GET | `/forgot-password` | `password.request` | `Auth\ForgotPassword` | `guest` |
| GET | `/reset-password/{token}` | `password.reset` | `Auth\ResetPassword` | `guest` |
| POST | `/logout` | `logout` | `LogoutController::__invoke` | `auth` |

## Admin

All admin routes have the `auth` middleware and the `dashboard` prefix.

| Method | URI | Name | Handler |
| --- | --- | --- | --- |
| GET | `/dashboard` | `admin.dashboard` | `Admin\Dashboard` |
| GET | `/dashboard/organisers` | `admin.organisers.index` | `Admin\Organisers\Index` |
| GET | `/dashboard/organisers/create` | `admin.organisers.create` | `Admin\Organisers\Form` |
| GET | `/dashboard/organisers/{organiser}/edit` | `admin.organisers.edit` | `Admin\Organisers\Form` |
| GET | `/dashboard/organisers/{organiser}/stripe` | `admin.organisers.stripe` | `Admin\Organisers\StripeConnect` |
| GET | `/dashboard/organisers/{organiser}/events` | `admin.events.index` | `Admin\Events\Index` |
| GET | `/dashboard/organisers/{organiser}/events/create` | `admin.events.create` | `Admin\Events\Form` |
| GET | `/dashboard/organisers/{organiser}/events/{event}/edit` | `admin.events.edit` | `Admin\Events\Form` |
| GET | `/dashboard/events/{event}/tickets` | `admin.tickets.index` | `Admin\Tickets\Index` |
| GET | `/dashboard/events/{event}/orders` | `admin.orders.index` | `Admin\Orders\Index` |
| GET | `/dashboard/events/{event}/attendees` | `admin.attendees.index` | `Admin\Attendees\Index` |
| GET | `/dashboard/events/{event}/attendees/export/{format}` | `admin.attendees.export` | `AttendeeExportController::__invoke` |
| GET | `/dashboard/events/{event}/check-in` | `admin.checkin.index` | `Admin\CheckIn\Scanner` |
| GET | `/dashboard/events/{event}/discounts` | `admin.discounts.index` | `Admin\DiscountCodes\Index` |
| GET | `/dashboard/events/{event}/questions` | `admin.questions.index` | `Admin\Questions\Index` |
| GET | `/dashboard/events/{event}/messages` | `admin.messages.index` | `Admin\Messaging\Index` |
| GET | `/dashboard/orders/{order}` | `admin.orders.show` | `Admin\Orders\Show` |
| GET | `/dashboard/payouts` | `admin.payouts.index` | `Admin\Payouts\Index` |

`{format}` is `csv` or `xlsx`; anything else is treated as `csv`. These are the only routes
for the admin screens. Creating, updating, deleting, publishing, refunding and checking in
all happen through Livewire action requests posted to the same URI, so there are no
additional route names to remember.

## Stripe and infrastructure

| Method | URI | Name | Handler | Notes |
| --- | --- | --- | --- | --- |
| POST | `/stripe/webhook` | `stripe.webhook` | `StripeWebhookController::handle` | CSRF-exempt; verified by signature |
| GET | `/stripe/connect/{organiser}/return` | `stripe.connect.return` | `StripeConnectController::return` | `manageStripe` check inside the controller |
| GET | `/stripe/connect/{organiser}/refresh` | `stripe.connect.refresh` | `StripeConnectController::refresh` | `manageStripe` check inside the controller |
| GET | `/up` | — | Framework health check | Registered by `withRouting(health: '/up')` |

The two Connect routes are in `routes/public.php` because Stripe redirects the browser to
them. They still require an authenticated owner: the controller aborts with 403 unless
`$request->user()->can('manageStripe', $organiser)`.

## Route model binding

| Parameter | Model | Key | Example |
| --- | --- | --- | --- |
| `{organiser}` | `App\Models\Organiser` | `slug` | `/dashboard/organisers/demo-events-ltd/edit` |
| `{event}` | `App\Models\Event` | `slug` | `/e/demo-conference-2026` |
| `{order}` | `App\Models\Order` | `reference` | `/orders/ORD-AB12CD34` |
| `{attendee}` | `App\Models\Attendee` | `reference` | `/tickets/TKT-AB12CD34EF` |
| `{token}` | none | — | Password reset token, a string |
| `{format}` | none | — | Export format string |

The key is returned by each model's `getRouteKeyName()`. Ticket and Question routes use the
numeric primary key implicitly; the admin components look them up through the event
relationship.

## Middleware and redirects

| Concern | Configuration |
| --- | --- |
| Guest redirect | `$middleware->redirectGuestsTo(fn () => route('login'))` in `bootstrap/app.php` |
| CSRF exemption | `validateCsrfTokens(except: ['stripe/webhook'])` |
| Authenticated pages | `Route::middleware('auth')` around the dashboard group |
| Guest-only pages | `Route::middleware('guest')` around the auth screens |
| JSON errors | `shouldRenderJsonWhen` for `api/*` or requests that expect JSON |

There is no rate-limiting middleware on the web routes, no signed URLs and no CORS
configuration, because there is no API. `Auth\Login` applies its own `RateLimiter` (5 attempts
per email/IP pair per 60 seconds).

## Notes on collisions

- `/orders/{order}` (public confirmation) and `/dashboard/orders/{order}` (admin) are
  distinct because of the prefix.
- `/tickets/{attendee}` and `/tickets/{attendee}/pdf` share a binding but different handlers.
- `/e/{event}` cannot collide with `/events` because the segment is literal for the index.
- Slugs are unique per organiser for events, so two organisers can both use
  `/e/summer-conference` only if their slugs differ; `Event::uniqueSlug` appends `-2`, `-3`
  within an organiser, and the `events` table has a unique index on
  (`organiser_id`, `slug`). Public URLs for events of different organisers can therefore
  still collide, because the public binding uses `slug` alone.
