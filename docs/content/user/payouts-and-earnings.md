---
title: Payouts and earnings
sidebar_position: 14
description: Review gross sales, platform fees, net earnings and refunds per event and per currency over any date range.
---

The payouts screen is a report of what each event has sold. It does not move money — ticket money already settles to your own Stripe account as each order is paid.

## Open the report

Select **Payouts** in the sidebar.

| Control | Notes |
| --- | --- |
| Organiser | **All organisers** by default, or one organiser, shown with its currency. |
| From | Start date, defaulting to 1 January of the current year. |
| To | End date, defaulting to today. |

Change any control and the report recalculates immediately.

## What is counted

- Only **completed** orders. Reserved, cancelled and refunded orders are not counted as sales.
- Only orders **paid** inside the chosen date range, so the range follows payment date rather than the event date.
- Attendee counts exclude cancelled tickets.

## Reading the table

| Column | Meaning |
| --- | --- |
| Event | Event title, linked to its orders. Also shows the organiser and currency. |
| Orders | Number of completed orders in the period. |
| Tickets sold | Attendees on those orders, excluding cancelled tickets. |
| Gross | Total paid by buyers, before fees and refunds. |
| Platform fee | The platform's application fee retained on those orders. |
| Net | Gross minus the platform fee — what the organiser receives. |
| Refunded | Amount refunded on those orders. Shown for information; it is not deducted from Gross or Net. |

A **Total** row per currency appears at the bottom of the table. If your organisers settle in more than one currency you get one total per currency, because amounts in different currencies are never added together.

:::note
Gross and Net are what the report counts; refunds sit in their own column. To see the money that actually reached your bank, subtract refunds from Net for the same period.
:::

## How the money actually reaches you

- Each sale is charged to the buyer and transferred to the organiser's connected Stripe account in the same transaction.
- The platform fee is kept automatically at that moment, which is why the Net column is what the organiser receives.
- Stripe pays out to the organiser's bank account on the payout schedule configured in Stripe, not on this screen. Use **Open Stripe dashboard** on the organiser's Stripe page for payout timing and bank statements.

## Reconciling a period

1. Pick the organiser and the date range you want to check.
2. Compare **Orders** and **Gross** against the orders list for that event, filtering by status **Completed**.
3. Compare **Refunded** against the refunded amounts on individual orders.
4. Compare **Net** against the balance movements in your Stripe dashboard.

:::warning
Changing an order after the period has closed — for example refunding a sale from last month — will change that earlier period's numbers when you re-run the report. Always re-run before quoting figures.
:::
