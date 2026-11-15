---
title: Managing organisers
sidebar_position: 4
description: Create and edit organisers, set currency, timezone and platform fee, and understand organiser public pages.
---

An organiser is the brand that owns events, branding and payouts. Everything in the dashboard sits inside an organiser: its events, its tickets, its orders and its Stripe account.

## The organisers screen

Open **Organisers** in the sidebar. Each organiser appears as a card showing:

- Its logo, or the first letter of its name if no logo is set.
- The organiser name and contact email.
- The number of events it owns.
- A **Stripe connected** or **Stripe not connected** badge.
- Links to **Events**, **Edit** and **Stripe**.

Select **New organiser** in the top-right corner to add another one.

## Create or edit an organiser

The form is the same for both. Fields:

| Field | Required | Notes |
| --- | --- | --- |
| Name | Yes | Public-facing brand name. |
| Email | Yes | Contact address for the organiser. Defaults to your login email for a new organiser. |
| Phone | No | Optional contact number. |
| About | No | Short description shown on the organiser's public page. |
| Logo | No | PNG or JPG up to 2 MB. Shown on the organisers list and the public page. |
| Currency | Yes | GBP, EUR or USD. Sets the currency for prices, orders and reports. |
| Timezone | Yes | Default timezone applied to new events. |
| Platform fee (%) | No | Leave blank to use the platform default of 5%. |

Select **Create organiser** or **Save changes**. Creating an organiser takes you straight to its event list.

:::warning
Choose the currency with care. Prices and orders are stored in that currency, so changing it later does not convert existing sales. The platform fee is a percentage of each sale and is retained automatically; leaving it blank uses the platform default.
:::

## The organiser's public page

Every organiser has a public page at **/o/your-organiser**, linked from the edit screen as **Public page**. It shows the logo, name, description and any upcoming events that are live, listed publicly and in the future.

If the page says there are no upcoming events, at least one of those conditions is not met — see [Creating events](creating-events.md).

## Moving between organisers

Use the sidebar to return to **Organisers**, then open the organiser you want. From an organiser you can reach:

- **Events** — the organiser's event list, with a **Create event** button.
- **Stripe settings** — connect or review the Stripe account (see [Connecting Stripe](stripe-connect.md)).
- **Edit organiser** — this form.

:::tip
The organiser's timezone is only a default. Each event stores its own timezone, which is what attendees see on the event page and on their tickets.
:::
