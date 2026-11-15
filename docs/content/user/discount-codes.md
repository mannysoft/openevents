---
title: Discount codes
sidebar_position: 8
description: Offer percentage or fixed-amount discounts at checkout, with limits on uses, order size and expiry.
---

Discount codes give buyers a percentage or fixed amount off the ticket subtotal. They are created per event, so a code for one event never works on another.

## Open the discount codes screen

Go to the event and select the **Discounts** tab. The table lists every code with its type, value, uses, minimum quantity, expiry and status.

Select **New discount code** to add one.

## Fields

| Field | Required | Notes |
| --- | --- | --- |
| Code | Yes | What buyers type at checkout. Stored in capitals, so `earlybird` becomes `EARLYBIRD`. Must be unique within the event. |
| Type | Yes | **Percentage** or **Fixed amount**. |
| Value | Yes | For percentage, a whole number from 1 to 100. For fixed amount, an amount in the event currency, at least the smallest unit (for example £0.01). |
| Quantity available | No | How many orders may use the code. Blank means unlimited. |
| Minimum quantity | Yes | Fewest tickets in the order before the code applies. Defaults to 1. |
| Expires at | No | Date and time after which the code stops working. |
| Active | — | Unticked codes are refused at checkout. |

Select **Create code** or **Save changes**.

## How a code is applied

- Buyers enter one code on the details step of checkout. Only one code can be used per order.
- A percentage code takes that share of the ticket subtotal; a fixed code takes its amount. The discount is never more than the subtotal, so an order cannot go negative.
- The code must be active, unexpired and have uses remaining, and the order must meet the minimum quantity.
- If a fixed discount covers the whole subtotal, the order total becomes zero and completes without a card payment.

:::warning
The available quantity cannot be set below the number of times the code has already been used. Raise the number instead of lowering it.
:::

## Managing codes

- **Activate** and **Deactivate** switch a code on or off without deleting it — handy when a promotion ends early.
- **Edit** changes any field, including the code itself.
- **Delete** removes the code after a confirmation prompt. Orders that already used it keep their recorded discount.

:::tip
Code matching ignores capitals and surrounding spaces, so you can write `EARLYBIRD` in your marketing and buyers can type `earlybird`.
:::
