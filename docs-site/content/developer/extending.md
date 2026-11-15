---
title: Extending Events
sidebar_position: 18
description: Concrete recipes for new admin screens, webhook events, currencies, policy abilities and migrations.
---

These recipes follow the patterns already in the repository. Read [architecture](architecture.md)
and [authorization](authorization.md) first if the layering is unfamiliar.

## Add an admin Livewire screen

**Component** — `app/Livewire/Admin/Reports/Index.php` (imports omitted for brevity):

```php
#[Layout('components.layouts.app')]
class Index extends Component
{
    public Event $event;

    public function mount(Event $event): void
    {
        Gate::authorize('view', $event);
        $this->event = $event;
    }

    public function render()
    {
        return view('livewire.admin.reports.index', [
            'orders' => $this->event->orders()->where('status', 'completed')->get(),
        ])->title($this->event->title.' · Reports');
    }
}
```

**View** — create `resources/views/livewire/admin/reports/index.blade.php` using the existing
Tailwind classes and include `<x-admin.event-nav :event="$event" />` above a heading and the
order count. No new CSS or Blade component is required.

**Route** — inside the existing `auth` + `dashboard` group in `routes/admin.php`:

```php
use App\Livewire\Admin\Reports\Index as ReportsIndex;

Route::livewire('/events/{event}/reports', ReportsIndex::class)->name('admin.reports.index');
```

**Navigation** — add a tab to `resources/views/components/admin/event-nav.blade.php`:

```php
['label' => 'Reports', 'route' => 'admin.reports.index', 'params' => [$event], 'active' => 'admin.reports.*'],
```

**Test** — add `route('admin.reports.index', $this->event)` to `$routes` in
`tests/Feature/AdminPagesTest.php::test_every_admin_page_renders_for_the_owner`. For a
dashboard-wide screen, drop the `Event` parameter, add the link to the sidebar in
`resources/views/components/layouts/app.blade.php`, and authorize inside the actions. Every
mutating action must call `Gate::authorize(...)` itself; `mount()` does not protect later
Livewire requests.

## Add a handled webhook event

Add a match arm in `StripeWebhookController::handle`:

```php
match ($event->type) {
    // existing arms...
    'payment_intent.requires_action' => $this->paymentRequiresAction($event),
    default => null,
};
```

Implement the handler, reusing `findOrder()` so lookup rules stay consistent:

```php
private function paymentRequiresAction(StripeEvent $event): void
{
    $order = $this->findOrder(
        $event->data->object->metadata->order ?? null,
        $event->data->object->id,
    );

    if ($order) {
        report(new \RuntimeException("Order {$order->reference} requires action"));
    }
}
```

Handlers return `void`; the controller answers `200 ok` for any parsed event. A handler that
throws produces a `500` and Stripe retries, so catch and `report()` `ApiErrorException` when a
failure should not block delivery, as `accountUpdated()` does.

Subscribe to the event in the Stripe dashboard and in `stripe listen`. There is no allow-list
in the controller — unhandled types are acknowledged silently — so a forgotten subscription
looks like a no-op. Add a case to `tests/Feature/StripeWebhookTest.php` with its `payload()`
helper, and update the subscription list in
[payments and webhooks](payments-and-webhooks.md).

## Add a currency

Add one entry alongside the existing three in `config/events.php`:

```php
'CHF' => ['symbol' => 'CHF ', 'decimals' => 2],
```

`decimals` must match the currency's Stripe minor-unit exponent; most use 2, zero-decimal
currencies such as JPY use `0`. Nothing else is needed for selection:
`Admin\Organisers\Form` validates `currency_code` against
`array_keys(config('events.currencies'))`, and `Money::decimals`, `symbol`, `format`,
`toMinor` and `toMajor` all read this config. Existing events and orders keep their stored
`currency_code`; there is no conversion. Cover it with `use App\Support\Money;` then
`$this->assertSame('CHF 12.50', Money::format(1250, 'CHF'));`.

## Add a policy ability

Add the method to the relevant policy, for example `App\Policies\EventPolicy`:

```php
public function export(User $user, Event $event): bool
{
    return $event->organiser->user_id === $user->id;
}
```

No registration is needed: `Gate::guessPolicyNamesUsing` maps `App\Models\Event` to
`App\Policies\EventPolicy`. Non-ownership conditions belong here too.
Call `Gate::authorize('export', $this->event)` before the work, or `@can('export', $event)` in
Blade, and test both sides with `actingAs` (`assertOk()` / `assertForbidden()`).
`AdminPagesTest` and `AttendeeExportTest` show the pattern.

## Add a migration

Generate an additive migration with
`php artisan make:migration add_requires_approval_to_events_table --table=events`:

```php
public function up(): void
{
    Schema::table('events', function (Blueprint $table) {
        $table->boolean('requires_approval')->default(false)->after('is_public');
        $table->unsignedInteger('deposit_minor')->default(0)->after('requires_approval');
    });
}

public function down(): void
{
    Schema::table('events', function (Blueprint $table) {
        $table->dropColumn(['requires_approval', 'deposit_minor']);
    });
}
```

Wire the columns into `$fillable` and `casts()` on `App\Models\Event`, then into the Livewire
form validation and Blade view. Money is `unsignedInteger` minor units. Foreign keys use `foreignId(...)->constrained()` with
an explicit `nullOnDelete()` or `cascadeOnDelete()`, and filter columns get an index like
`orders`' `['event_id', 'status']`. Keep migrations additive and never edit one that has run in
production. SQLite backs the test suite, so avoid `change()` unless verified on both engines.
Finish with `php artisan migrate`, `php artisan test` and `vendor/bin/pint --dirty`.
