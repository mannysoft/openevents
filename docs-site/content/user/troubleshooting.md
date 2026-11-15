---
title: Troubleshooting
sidebar_position: 16
description: Fix the most common problems with publishing events, selling tickets, refunds, check-in and email delivery.
---

Most problems come down to an event not being published, a ticket not being on sale, or Stripe onboarding not being finished. Work through the entry that matches what you see.

## Sales are not reaching the organiser's account

Publishing and checkout work without a connected Stripe account: the platform takes the
payment on its own Stripe account instead.

Open **Organisers → your organiser → Stripe** and check **Charges enabled**. If it says
**No**, select **Continue onboarding**, finish Stripe's steps, then select **Refresh status**.
Once the badge on the organiser reads **Stripe connected**, new sales transfer directly to
the organiser.

## The public page says tickets are not on sale

The **Get tickets** button appears as soon as at least one ticket is on sale.

Check each ticket on the **Tickets** tab:

| Check | Fix |
| --- | --- |
| The ticket is hidden | Select **Show** on the row. |
| The sales start is in the future | Change or clear **Sales start**. |
| The sales end has passed | Change or clear **Sales end**. |
| Remaining shows **Sold out** | Raise **Quantity available**, or leave it blank for unlimited. |
| There are no tickets at all | Create one with **New ticket**. |

## My event is not on the public site

An event appears at **/events** and on your organiser page only when all of these are true:

- Its status is **Live** (published, not draft).
- **Show this event on the public site** is ticked on the event form.
- The start date is still in the future.

Past events drop off the listing automatically, but existing links to them keep working for anyone who has one.

## A discount code is refused

Check the code on the **Discounts** tab: it must be **Active**, not past its **Expires at** time, still have uses left, and the order must meet its **Minimum quantity**. Codes belong to one event, so a code created on another event will not work.

## The Refund button is greyed out

Refunds need a completed order, a payment taken through Stripe, and an unrefunded balance. The button is disabled when:

- the order is still **Reserved** and has not been paid;
- the order has already been fully refunded;
- the order total was zero, so no card payment was taken.

For a reserved order, use **Cancel order** instead. See [Orders and refunds](orders-and-refunds.md).

## An attendee is missing from the list

The attendee list only includes attendees whose order is **Completed**. Check the event's **Orders** tab with the status filter set to **Reserved** — if the order is there, the payment has not been confirmed. Cancelled and refunded attendees still appear in the list, so also check the **Status** filter is not hiding them.

## Check-in refuses a ticket

| Message | What to do |
| --- | --- |
| No ticket found | Check you are on the right event, and retype the reference without extra words. Scanning the QR code avoids typing errors. |
| This ticket has been refunded | The refund stands. Issue a new ticket only if you intend to honour it, then check that order again. |
| This ticket has been cancelled | Use **Restore** on the attendee list if the cancellation was a mistake. |
| The order for this ticket is not completed | The order was never paid; check the orders list. |
| Already checked in | Someone has used this ticket. Check the name before admitting anyone. |

More detail is on [Check-in](check-in.md).

## The camera scanner will not start

Camera access needs a secure connection (HTTPS) and permission from the browser. Allow the camera when prompted, stop and start the scanner again, and keep a data connection — the scanner loads a small library from the web. Typing the reference always works and needs no camera.

## Attendees did not receive an email

- The order confirmation is sent in the background once payment is confirmed; a fresh order can take a minute.
- Messages sent from the **Messages** tab are queued, so they also take a moment to start arriving.
- Ask recipients to check spam or junk, and confirm the email address on the attendee row is right — use **Edit** to fix a typo.
- If no email reaches anyone at all, the outgoing mail or the background worker on the server needs attention. The [developer guide](../developer/index.md) covers both.

## The Stripe onboarding link expired

Old onboarding links stop working. Select **Continue onboarding** on the Stripe page to get a fresh one; the dashboard will already have brought you back with the message *Your onboarding link expired, please start again.*

## The numbers do not match what I expected

- The payouts report counts **completed** orders paid inside the date range, not every order ever created.
- Fully refunded orders are excluded from that report; **Refunded** shows the amounts separately, and Net is Gross minus the platform fee.
- Totals are split per currency and never combined.
- Two events with the same title have separate orders and separate totals.

:::tip
When something looks wrong, note the order or attendee reference first. References make it easy to find the same record in the orders list, the attendee list and the check-in log.
:::
