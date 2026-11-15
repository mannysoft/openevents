---
title: Money and currency
sidebar_position: 6
description: Integer minor units, the Money helper, currency metadata and where conversions happen in the app.
---

Every amount in the database is an integer in the currency's smallest unit: pence for GBP,
cents for EUR and USD. There are no floats or decimals in money columns, and no exchange-rate
conversion anywhere in the application.

## Storage

| Column | Meaning |
| --- | --- |
| `tickets.price` | Unit price in minor units |
| `order_items.unit_price`, `order_items.total` | Snapshot of price and line total |
| `orders.subtotal` | Sum of line totals |
| `orders.discount_total` | Discount applied to the subtotal |
| `orders.tax_total` | Not calculated by checkout; reserved for tax support |
| `orders.total` | `max(0, subtotal - discount_total)` |
| `orders.application_fee_amount` | Platform fee retained, in minor units |
| `orders.refunded_amount` | Monotonic refunded total, in minor units |
| `discount_codes.value` | Whole percent for `percentage`, minor units for `fixed` |

The schema uses `unsignedInteger` for money, so a negative value cannot be stored. Services
clamp where necessary: `DiscountCode::discountFor` caps the discount at the subtotal,
`CheckoutService::applicationFee` clamps to `[0, total]`, and `OrderService::syncRefunded`
caps at the order total.

## The Money helper

`App\Support\Money` is a static utility with no state.

| Method | Example | Result |
| --- | --- | --- |
| `Money::decimals('GBP')` | | `2` |
| `Money::symbol('GBP')` | | `£` |
| `Money::format(2500, 'GBP')` | | `£25.00` |
| `Money::toMinor('25.00', 'GBP')` | | `2500` |
| `Money::toMinor(12.5, 'GBP')` | | `1250` |
| `Money::toMajor(2500, 'GBP')` | | `25.0` |

`decimals()` and `symbol()` read `config("events.currencies.{$currency}...")`. If a currency
is missing from the config, `decimals()` falls back to `2` and `symbol()` falls back to the
code followed by a space (`"CHF "`). `format()` calls `number_format` with the configured
decimals, so thousands are comma-separated and the decimal point is a dot.

`toMinor()` rounds half-up after casting to float, so it is suitable for form input but not
for repeated arithmetic. Do calculation in integer minor units and convert only at the
edges.

## Currency metadata

`config/events.php` defines the supported currencies:

| Code | Symbol | Decimals |
| --- | --- | --- |
| `GBP` | `£` | 2 |
| `EUR` | `€` | 2 |
| `USD` | `$` | 2 |

The fallback currency is `config('events.default_currency')`, which comes from
`EVENTS_CURRENCY` and defaults to `GBP`. The organiser form validates `currency_code`
against `array_keys(config('events.currencies'))`, so a currency must be added to that array
before it can be selected. The recipe is in [extending](extending.md).

## Where conversions happen

| Location | Direction | Notes |
| --- | --- | --- |
| `App\Livewire\Admin\Tickets\Index::save` | major → minor | `Money::toMinor($this->price, $this->currency())` |
| `App\Livewire\Admin\Orders\Show::refund` | major → minor | Converts the refund input before calling `RefundService` |
| `App\Livewire\Admin\Orders\Show::refundableInput` | minor → major | `Money::toMajor` then `number_format` |
| Blade views | minor → display | `Money::format(...)` |
| `CheckoutService::createPaymentIntent` | minor → Stripe | Sends `amount` as minor units and lower-case ISO currency |

Stripe expects the amount in minor units for these currencies, so no scaling happens at the
Stripe boundary. The currency is lower-cased only because Stripe requires that form:
`strtolower($order->currency_code)`.

## Fees and refunds

- The application fee is computed in `CheckoutService::applicationFee`:
  `round(total * feePercent / 100)`, clamped to the order total. `feePercent` is
  `Organiser::feePercent()`, which prefers the organiser override and falls back to
  `STRIPE_PLATFORM_FEE_PERCENT`.
- The computed fee is persisted on the order as `application_fee_amount` before the payment
  intent is confirmed, so later fee changes do not rewrite history.
- A refund sends `refund_application_fee` only when the stored fee is greater than zero, and
  `reverse_transfer: true`. Stripe returns the fee proportionally to a partial refund.
- `orders.refunded_amount` is never decreased: `applyRefundedTotal` returns early if the new
  total is smaller. This makes webhook retries safe. See [order lifecycle](order-lifecycle.md).

## Multi-currency behaviour

- An event copies `currency_code` from its organiser at creation and keeps it afterwards.
  Changing the organiser currency does not migrate existing events or orders.
- An order copies the event currency and keeps it; `Order::totalLabel()` formats using the
  order's currency.
- The dashboard and payouts screens group totals by `currency_code` and render one line per
  currency. They never sum across currencies.
- There is no conversion between currencies, no price rounding rules per market, and no
  locale-aware number formatting beyond `number_format`.
