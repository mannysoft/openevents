---
title: Livewire components
sidebar_position: 10
description: Inventory of full-page Livewire components, their routes, layouts and authorization entry points.
---

Every screen is a full-page Livewire 4 component registered with `Route::livewire()`. The
component class lives in `app/Livewire`, its view in `resources/views/livewire`, and its
layout is declared with `#[Layout(...)]`. Views are resolved by kebab-cased class name, for
example `Admin/Organisers/StripeConnect` → `livewire/admin/organisers/stripe-connect.blade.php`.

## Admin components

All admin components use `#[Layout('components.layouts.app')]` and live under `/dashboard`.

| Component | Route name | URI | View | Notes |
| --- | --- | --- | --- | --- |
| `Admin\Dashboard` | `admin.dashboard` | `/dashboard` | `admin/dashboard` | Totals grouped by currency, onboarding prompt, recent orders; `#[Title('Dashboard')]` |
| `Admin\Organisers\Index` | `admin.organisers.index` | `/dashboard/organisers` | `admin/organisers/index` | Lists the current user's organisers |
| `Admin\Organisers\Form` | `admin.organisers.create`, `admin.organisers.edit` | `/dashboard/organisers/create`, `/dashboard/organisers/{organiser}/edit` | `admin/organisers/form` | Creates organisers with `user_id = auth()->id()` |
| `Admin\Organisers\StripeConnect` | `admin.organisers.stripe` | `/dashboard/organisers/{organiser}/stripe` | `admin/organisers/stripe-connect` | Onboarding, refresh, requirements, dashboard link |
| `Admin\Events\Index` | `admin.events.index` | `/dashboard/organisers/{organiser}/events` | `admin/events/index` | Events for one organiser |
| `Admin\Events\Form` | `admin.events.create`, `admin.events.edit` | `/dashboard/organisers/{organiser}/events/create`, `.../{event}/edit` | `admin/events/form` | Save, publish, unpublish, delete; cover upload |
| `Admin\Tickets\Index` | `admin.tickets.index` | `/dashboard/events/{event}/tickets` | `admin/tickets/index` | CRUD, hide, reorder, sales window |
| `Admin\Orders\Index` | `admin.orders.index` | `/dashboard/events/{event}/orders` | `admin/orders/index` | Status/search filters |
| `Admin\Orders\Show` | `admin.orders.show` | `/dashboard/orders/{order}` | `admin/orders/show` | Refund modal, cancel reservation |
| `Admin\Attendees\Index` | `admin.attendees.index` | `/dashboard/events/{event}/attendees` | `admin/attendees/index` | Filters, edit modal, cancel/restore, resend ticket |
| `Admin\CheckIn\Scanner` | `admin.checkin.index` | `/dashboard/events/{event}/check-in` | `admin/checkin/index` | Manual reference and camera scanner |
| `Admin\DiscountCodes\Index` | `admin.discounts.index` | `/dashboard/events/{event}/discounts` | `admin/discounts/index` | CRUD, activate/deactivate |
| `Admin\Questions\Index` | `admin.questions.index` | `/dashboard/events/{event}/questions` | `admin/questions/index` | CRUD, ordering |
| `Admin\Messaging\Index` | `admin.messages.index` | `/dashboard/events/{event}/messages` | `admin/messages/index` | Queues `SendAttendeeMessages` and writes `MessageLog` |
| `Admin\Payouts\Index` | `admin.payouts.index` | `/dashboard/payouts` | `admin/payouts/index` | Earnings by event and currency with a date range |

`Admin\Events\Index`, `Admin\Events\Form` and `Admin\Organisers\*` bind `Organiser` by slug;
`Admin\Orders\Show` binds `Order` by reference; every other event-scoped component binds
`Event` by slug and loads related records through `$event->...` relationships so a mismatched
id cannot leak another organiser's data.

## Public components

All use `#[Layout('components.layouts.public')]`.

| Component | Route name | URI | Behaviour |
| --- | --- | --- | --- |
| `Public\EventIndex` | `events.index` | `/events` | Published, upcoming events paginated 12 per page |
| `Public\OrganiserPage` | `organisers.show` | `/o/{organiser}` | Organiser's published upcoming events |
| `Public\EventPage` | `events.show` | `/e/{event}` | 404 unless `is_public` and live; visible tickets only |
| `Public\Checkout` | `checkout` | `/e/{event}/checkout` | Three-step wizard (tickets, details, payment) |
| `Public\OrderConfirmation` | `orders.confirmation` | `/orders/{order}` | Order summary with `refreshStatus()` |
| `Public\Ticket` | `tickets.show` | `/tickets/{attendee}` | 404 unless the order is completed; QR via `Qr::svg` |

`Public\EventIndex` and `Public\OrganiserPage` use `Livewire\WithPagination`.

## Auth components

All use `#[Layout('components.layouts.auth')]` and sit behind the `guest` middleware.

| Component | Route name | URI |
| --- | --- | --- |
| `Auth\Login` | `login` | `/login` |
| `Auth\Register` | `register` | `/register` |
| `Auth\ForgotPassword` | `password.request` | `/forgot-password` |
| `Auth\ResetPassword` | `password.reset` | `/reset-password/{token}` |

`Auth\ResetPassword` marks the token `#[Locked]`. Logout is not a component; it is
`Auth\LogoutController::__invoke` on `POST /logout` behind `auth`.

## Component conventions

| Convention | Where to see it |
| --- | --- |
| `mount()` binds models, applies policy checks, loads initial data | Every admin component |
| `#[Locked]` protects models that must not be swapped by the client | `Public\Checkout`, `Admin\Attendees\Index`, `Admin\CheckIn\Scanner`, `Auth\ResetPassword` |
| `#[On('barcode-scanned')]` receives a browser event | `Admin\CheckIn\Scanner::handleScan` |
| Services are method-injected, not stored as properties | `Checkout::pay`, `Orders\Show::refund`, `Scanner::handleScan` |
| Views may call `->title(...)`; the layout otherwise uses `$heading`/`$title` | Admin and Stripe components |
| `wire:navigate` is used for same-app links | Admin and public nav |
| Flash messages use `session()->flash('status' / 'error', ...)` | Layouts render `status` |
| Forms validate in the action with `$this->validate([...])`, then call services/models | All forms |

## Checkout wizard

`App\Livewire\Public\Checkout` is the most stateful component:

| State | Step |
| --- | --- |
| `step = 1` | Ticket quantities (`quantities` keyed by ticket id); `continueToDetails()` checks Stripe readiness |
| `step = 2` | Customer name/email, per-attendee rows, required question answers, discount code; `pay()` validates, reserves the order and creates the intent |
| `step = 3` | `clientSecret` and `total` are set; the Blade view mounts the Stripe Payment Element |

`pay()` cancels the reservation and reports the error when Stripe's API throws. For a
zero-total order it completes the order immediately and redirects to the confirmation page.

## Scanner

The check-in view loads `html5-qrcode` from unpkg at runtime and dispatches
`barcode-scanned` with `{ reference }`. `Scanner::handleScan` normalises the reference and
delegates to `lookupAndCheckIn`, which scopes the lookup to the bound event and calls
`OrderService::checkIn`. There is no offline mode and no batched check-in.

## Shared Blade pieces

| View | Purpose |
| --- | --- |
| `components/layouts/app.blade.php` | Admin sidebar (Dashboard, Organisers, Payouts), header, sign-out, flash |
| `components/layouts/public.blade.php` | Public header/footer and flash messages |
| `components/layouts/auth.blade.php` | Centred auth card |
| `components/admin/event-nav.blade.php` | Event-scoped tab bar; add new event screens here |
| `components/ui/card.blade.php` | Small card wrapper |
