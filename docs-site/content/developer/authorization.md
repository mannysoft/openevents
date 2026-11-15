---
title: Authorization
sidebar_position: 12
description: Policy discovery, the ownership rules behind every ability, and which pages are intentionally public.
---

Authorization uses Laravel gates and policies with no roles, permissions package or admin
flag. Every check reduces to "does this user own the organiser?".

## Policy discovery

`App\Providers\AppServiceProvider` registers a naming convention:

```php
Gate::guessPolicyNamesUsing(fn (string $model) => 'App\\Policies\\'.class_basename($model).'Policy');
```

| Model | Policy |
| --- | --- |
| `App\Models\Organiser` | `App\Policies\OrganiserPolicy` |
| `App\Models\Event` | `App\Policies\EventPolicy` |
| `App\Models\Order` | `App\Policies\OrderPolicy` |
| `App\Models\Attendee` | `App\Policies\AttendeePolicy` |

`Ticket`, `DiscountCode`, `Question`, `Answer`, `OrderItem` and `MessageLog` have no policy.
They are always reached through an authorised parent (`Gate::authorize('view'|'update', $event)`)
and looked up through the parent's relationship, which prevents cross-organiser access.

## Abilities

| Ability | Model | Rule |
| --- | --- | --- |
| `view` | `Organiser` | `organiser->user_id === user->id` |
| `update` | `Organiser` | Same |
| `delete` | `Organiser` | Same |
| `manageStripe` | `Organiser` | Same |
| `view` | `Event` | `event->organiser->user_id === user->id` |
| `update` | `Event` | Same |
| `delete` | `Event` | Same |
| `publish` | `Event` | Owner **and** `event->organiser->stripeReady()` |
| `view` | `Order` | `order->organiser->user_id === user->id` |
| `refund` | `Order` | Same |
| `view` | `Attendee` | `attendee->event->organiser->user_id === user->id` |
| `update` | `Attendee` | Same |

There are no `create` abilities; a new organiser is always created for `auth()->id()`, and a
new event, ticket or question is always created through an already-authorised organiser or
event.

`publish` is the only ability with a non-ownership condition. `Events\Form::publish` also
performs an early `stripeReady()` check with a flash message, then calls
`Gate::authorize('publish', $this->event)`, so both the UI and the policy enforce readiness.

## Where checks run

| Entry point | Check |
| --- | --- |
| Every admin component `mount()` | `Gate::authorize('view', ...)` on the bound model |
| Every mutating action | `Gate::authorize('update' / 'delete' / 'publish' / 'refund', ...)` before writing |
| `AttendeeExportController` | `Gate::authorize('view', $event)` |
| `StripeConnectController::return` / `refresh` | `abort_unless($request->user()?->can('manageStripe', $organiser), 403)` |
| `Admin\Payouts\Index` | `Gate::authorize('view', $selectedOrganiser)` when an organiser filter is selected |
| `Admin\Attendees\Index::findAttendee` | Looks up within `$this->event->attendees()` then `Gate::authorize('update', $attendee)` |

Admin components are not protected by a route middleware beyond `auth`. A logged-in user can
reach the URL of another organiser's record; `mount()` then throws an authorization
exception, which Laravel renders as **403**. Missing records throw a model-not-found
exception and render as **404**.

Mutation actions re-authorize rather than trusting `mount()`, because Livewire action
requests are independent HTTP requests with client-supplied component state.

## Public pages

The following routes intentionally have no authorization:

| Page | Access control |
| --- | --- |
| `/events`, `/o/{organiser}`, `/e/{event}` | Only published (`live` + `is_public`) records are shown |
| `/e/{event}/checkout` | 404 unless the event is public and live |
| `/orders/{order}` | Order reference in the URL |
| `/tickets/{attendee}` | Attendee reference in the URL; 404 unless the order is completed |
| `/tickets/{attendee}/pdf` | Attendee reference in the URL |

The references are generated with `Str::random` (`ORD-` + 8, `TKT-` + 10 upper-case
characters), so they are unguessable in practice. Treat them as bearer tokens: anyone with
the link can view the order or ticket. There is no login requirement for attendees and no
ability to revoke an individual link short of changing the reference.

`Public\OrderConfirmation` does not verify that the order is completed; it renders the
current status, which is how a buyer sees "processing" immediately after payment. The ticket
page is stricter and returns 404 until the order is completed.

## Guests and sessions

- Unauthenticated access to a route with `auth` triggers
  `redirectGuestsTo(route('login'))`, so the user lands on `/login`.
- `POST /logout` is behind `auth`; `LogoutController` calls `Auth::logout()`, invalidates the
  session and regenerates the CSRF token.
- Auth screens are behind `guest`. Laravel's `RedirectIfAuthenticated` looks for a route
  named `dashboard` and then `home`; this app has `home` (`/` → `/events`), so a signed-in
  user is redirected to `/` and from there to `/events`.

## CSRF and the webhook

`stripe/webhook` is excluded from CSRF validation because Stripe cannot send a Laravel token.
Its authenticity comes from `Stripe\Webhook::constructEvent` and `STRIPE_WEBHOOK_SECRET`. If
the secret is blank the payload is parsed unverified; that is intended for local development
only. See [payments and webhooks](payments-and-webhooks.md).

## Adding an ability

Add the method to the relevant policy class, then call `Gate::authorize()` in the component
action before mutating. The policy is discovered automatically; no registration is needed.
The full recipe is in [extending](extending.md).
