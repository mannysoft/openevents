---
title: Code style
sidebar_position: 14
description: Pint formatting, CI enforcement and the PHP, Livewire and Blade conventions used across the codebase.
---

Formatting is enforced by **Laravel Pint**, installed as `laravel/pint ^1.27`. There is no
`pint.json` in the repository, so Pint runs its default **`laravel` preset**. Do not add a
custom config unless you intend to change every file at once.

## Commands

```bash
vendor/bin/pint           # fix files in place
vendor/bin/pint --test    # check only; this is what CI runs
vendor/bin/pint --dirty   # fix only files changed against git
```

CI (`.github/workflows/laravel.yml`) runs `vendor/bin/pint --test` after `php artisan test`.
A style failure fails the build. There is no separate lint step for Blade, JavaScript or CSS.

## Editor settings

`.editorconfig` applies to the whole repository:

| Setting | Value |
| --- | --- |
| `charset` | `utf-8` |
| `end_of_line` | `lf` |
| `indent_style` | `space` |
| `indent_size` | `4` |
| `insert_final_newline` | `true` |
| `trim_trailing_whitespace` | `true` (disabled for `*.md`) |
| YAML indent | `2` |

Pint and the editor config agree on four-space indentation, so manually indented code is not
reformatted on the next run.

## Observed conventions

### PHP

| Convention | Example |
| --- | --- |
| No `declare(strict_types=1)` | No file in `app/`, `tests/` or `database/` uses it |
| PSR-4 `App\` → `app/`, one class per file | `App\Services\OrderService` in `app/Services/OrderService.php` |
| Typed properties and return types everywhere | `public string $email = '';`, `private function tickets(): Collection` |
| Constructor property promotion, often `readonly` | `public function __construct(private readonly StripeClient $stripe) {}` |
| Early returns over nested `else` | `OrderService::complete`, `Money` helpers |
| `match` for enum/value branching | Webhook `match ($event->type)`, enum `label()` |
| Backed string enums with a `label()` helper | `App\Enums\*` |
| `$fillable` arrays on most models; `#[Fillable]`/`#[Hidden]` attributes on `User` | `app/Models/User.php` |
| Casts in a `protected function casts(): array` method | `Event`, `Order`, `Ticket`, `Attendee` |
| Relationships have explicit return types | `BelongsTo`, `HasMany`, `Attribute` |
| PHPDoc only for non-obvious types | Array shapes in `ConnectService::requirements` and `SendAttendeeMessages` |
| `::class` imports, not string class names | `use App\Mail\OrderConfirmation;` then `new OrderConfirmation(...)` |
| Alphabetically sorted imports | Pint's `ordered_imports` fixer |
| Single quotes unless interpolation is needed | `'This event is not currently on sale.'` vs `"\"{$ticket->title}\" is not on sale."` |
| `fn () => ...` arrow functions for one-liners | Scopes, `mapWithKeys`, `when` callbacks |
| Validation rules as arrays | `['required', 'string', 'max:255']` |
| `Gate::authorize()` before mutations | All admin components |
| No custom exception classes | `ValidationException` and Laravel's HTTP exceptions |

Services are concrete classes with no interfaces and no abstract base. There is no repository
layer: Eloquent models are used directly.

### Livewire

| Convention | Example |
| --- | --- |
| `#[Layout(...)]` attribute on the class | All components |
| `#[Title(...)]` or `->title(...)` for page titles | `Admin\Dashboard`, `Admin\Orders\Show::render` |
| `#[Locked]` for models the client must not swap | `Public\Checkout::$event`, `Auth\ResetPassword::$token` |
| `#[On('event')]` for browser events | `Admin\CheckIn\Scanner::handleScan` |
| Public typed properties for form state | `public string $title = '';` |
| `mount()` authorizes and loads, actions authorize again | `Admin\Tickets\Index` |
| Services method-injected into actions | `pay(OrderService $orders, CheckoutService $checkout)` |
| Views resolved by convention | `resources/views/livewire/...` kebab-case |
| `$this->validate()` then model/service call | Every form action |
| `session()->flash('status'/'error', ...)` for feedback | Layouts render `status` |

### Blade

| Convention | Example |
| --- | --- |
| Tailwind 4 utility classes inline; no per-page CSS | `resources/views/**` |
| `x-ui.card` component for panels | `resources/views/components/ui/card.blade.php` |
| `@use('App\Support\Money')` then `Money::format(...)` | `resources/views/livewire/admin/dashboard.blade.php` |
| `wire:model`, `wire:click`, `wire:navigate` | Admin and public views |
| `@error('field')` with a rose-coloured message | Forms |
| `@script ... @endscript` for page JavaScript | Checkout payment element, scanner |
| Blade comments only where markup is non-obvious | `resources/views/pdf/ticket.blade.php` |

Escaping: use `{{ }}` for output. `{!! !!}` is not used for user content; QR SVGs are passed
through a dedicated `Qr` helper and rendered where required.

### JavaScript and CSS

`resources/js/app.js` is empty. JavaScript that a page needs is inline in that page's Blade
view (`@script`), as with Stripe's Payment Element and the `html5-qrcode` scanner. Tailwind 4
is configured in `resources/css/app.css` with `@source` globs and an `@theme` font variable.
There is no ESLint, Prettier or Stylelint setup.

## Practical rules

- Run `vendor/bin/pint --dirty` before committing. Committing unformatted code will fail CI.
- Keep controllers thin. If an endpoint needs more than a gate check and a query, move the
  logic to a service or the model.
- Keep Stripe calls inside `app/Services/Stripe`. Components and controllers call the service.
- Use `Money` for every conversion and format. Never do `$minor / 100`.
- Prefer relationship-scoped lookups (`$event->tickets()->findOrFail($id)`) over global
  `Ticket::findOrFail($id)` in admin code; the scope is what makes the tenant boundary hold.
