---
title: Tickets
sidebar_position: 7
description: Create ticket types, set prices, quantities and sales windows, and control which tickets buyers can see.
---

Tickets are what buyers actually purchase. An event can have as many ticket types as you like, each with its own price, quantity and sales window.

## Open the ticket list

Go to **Dashboard → Organisers → your organiser → Events → your event**, then select the **Tickets** tab.

The table lists every ticket with **Price**, **Available**, **Sold**, **Remaining**, **Sales window** and **Status**, and buttons to move, edit, hide and delete.

## Create or edit a ticket

Select **New ticket**, or **Edit** on an existing row. The form fields:

| Field | Required | Notes |
| --- | --- | --- |
| Title | Yes | For example *General admission*. Buyers see this at checkout and on their ticket. |
| Description | No | Short text shown under the title. |
| Price | Yes | In the event's currency, to two decimals. Enter 0 for a free ticket. |
| Quantity available | No | Leave blank for unlimited. Cannot be set below the number already sold. |
| Min / order | Yes | Smallest number a buyer may take in one order. Defaults to 1. |
| Max / order | Yes | Largest number a buyer may take in one order. Defaults to 10. |
| Sales start | No | Before this time the ticket is off sale. Blank means no start limit. |
| Sales end | No | After this time the ticket is off sale. Blank means no end limit. |
| Hide this ticket from the public event page | — | Tick to keep a ticket out of the public page and out of checkout. |

Select **Create ticket** or **Save changes**.

:::note
New tickets are added at the bottom of the order. The **Sold** and **Available** numbers are shown so you can see how much of each allocation is left before editing.
:::

## Statuses

| Status | Meaning |
| --- | --- |
| On sale | Visible and inside its sales window, with tickets remaining. |
| Off sale | Outside its sales window, or sold out. |
| Hidden | Excluded from the public page and from checkout. |

When the quantity available has been reached, the **Remaining** column shows *Sold out* instead of a number.

The public event page only shows tickets that are not hidden, and the **Get tickets** button only appears when the organiser's Stripe account is ready and at least one ticket is on sale.

## Reordering, hiding and deleting

- Use the **↑** and **↓** buttons to change the order tickets appear in at checkout and on the event page.
- **Hide** and **Show** toggle visibility without deleting anything. Useful for a ticket type you want to bring back later.
- **Delete** removes the ticket after a confirmation prompt. This cannot be undone, and sales already made keep their own record on the order.

:::caution
Setting **Quantity available** below the number already sold is rejected with the message *The quantity available cannot be less than the number already sold.* Raise the number instead, or leave it blank for unlimited.
:::

:::tip
For a free event, create a ticket priced at 0. If the order total comes to nothing, checkout completes straight away without asking for card details — no Stripe payment is taken.
:::
