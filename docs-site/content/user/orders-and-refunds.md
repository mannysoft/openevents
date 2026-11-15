---
title: Orders and refunds
sidebar_position: 10
description: Review orders for an event, cancel unpaid reservations, and issue full or partial refunds through Stripe.
---

Every purchase creates an order. An order holds the ticket lines, the buyer's details and one attendee record per ticket. From an order you can look at what was bought and refund it if you need to.

## The orders list

Open the event and select the **Orders** tab. Three cards at the top summarise **Gross completed revenue**, the number of **Orders**, and the amount **Refunded**.

Above the table:

| Filter | Options |
| --- | --- |
| Status | All statuses, or a single status. |
| Search | Order reference, buyer email, first name or last name. |
| Date ordering | Newest first or oldest first. |
| Clear | Appears once a filter is active and resets them. |

The table shows each order's **Reference**, **Customer**, **Status**, **Items**, **Total**, **Paid** date and **Refunded** amount. Select a reference to open the order.

## Order statuses

| Status | Meaning |
| --- | --- |
| Reserved | Checkout started but the payment has not been confirmed yet. Stock is not counted until payment succeeds. |
| Completed | Paid. Attendees are active and appear on the attendee list. |
| Cancelled | An unpaid reservation that was abandoned, failed or cancelled by you. |
| Refunded | Fully refunded. The order total has been returned. |

## The order page

The order screen has three areas:

- **Items** — each ticket line with unit price, quantity and line total, then subtotal, any discount (with the code used), any tax, the total and the amount refunded.
- **Attendees** — one row per ticket with its reference, name, email, ticket type and status.
- **Summary and Payment** — buyer details, key dates, payment intent, connected account, application fee, net to the organiser, and the refundable balance.

## Cancelling an unpaid order

For a **Reserved** order, select **Cancel order** and confirm. The reservation is closed and its attendees are removed. This does not refund anything because no money was taken.

Reservations are also cancelled automatically when a payment fails or is abandoned.

## Refunding an order

1. Open the order and select **Refund**.
2. The dialog shows the **Refundable balance** — the order total minus anything already refunded.
3. Enter an **Amount**. It is pre-filled with the full refundable balance, so reduce it for a partial refund.
4. Select **Issue refund**.

The refund goes back to the buyer's original payment method through Stripe. You cannot refund more than the refundable balance; the field rejects anything larger.

:::note
The **Refund** button is greyed out for orders that are not completed, orders that are fully refunded, and free orders that were never charged through Stripe. Refunds are only possible once the payment has cleared, so give a fresh order a minute before trying.
:::

## What a refund changes

- The order's refunded amount goes up and the refundable balance goes down.
- A **partial** refund leaves the order **Completed**; attendees keep their tickets.
- A **full** refund sets the order to **Refunded** and marks every attendee on it as refunded.
- The money is pulled back from the organiser's Stripe balance and the platform's fee for that amount is returned proportionally.
- Refunded attendees cannot be checked in. See [Check-in](check-in.md).

## Emails sent by orders

- A **confirmation email** is sent automatically, in the background, when a payment succeeds. It lists the tickets with their references.
- A **ticket email** can be sent again per attendee from the attendee list — see [Attendees](attendees.md).

:::tip
Refund the order rather than cancelling individual tickets when money has changed hands. Ticket cancellation on the attendee list does not return any money.
:::
