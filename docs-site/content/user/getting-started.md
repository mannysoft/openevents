---
title: Getting started
sidebar_position: 2
description: Create your account, connect Stripe, build an event and put tickets on sale for the first time.
---

This page takes you from a blank dashboard to a published event with tickets on sale. It usually takes about fifteen minutes, most of which is Stripe's identity checks.

## What you need

- An email address you can access.
- Your organisation's name and a short description for its public page.
- Business details, a bank account and identity documents for Stripe.
- A modern browser. For check-in later, a phone or tablet with a camera.

## 1. Create your account

Open the site and choose **Sell tickets** in the top-right corner, or **Create an account** on the sign-in page.

| Field | Notes |
| --- | --- |
| Name | Your own name, shown in the dashboard header. |
| Email | Used to sign in and to reset your password. |
| Password | At least 8 characters. |
| Confirm password | Must match the password above. |

Select **Create account**. You are signed in straight away and land on **Organisers**.

## 2. Create your first organiser

Select **New organiser** and fill in the details:

- **Name** — the public-facing brand, for example *Demo Events Ltd*.
- **Email** and **Phone** — how attendees can reach you.
- **About** — a short paragraph shown on your public organiser page.
- **Currency** — GBP, EUR or USD. This fixes the currency for prices, orders and reports.
- **Timezone** — the default timezone for new events.

Select **Create organiser**. You are taken to that organiser's event list.

The full field reference is on [Managing organisers](organisers.md).

## 3. Connect Stripe

Open **Stripe** on the organiser and select **Connect Stripe**. Stripe collects your business details and bank account, then returns you to the dashboard.

**You cannot publish an event until Stripe onboarding is complete.** Until then the event stays in draft and checkout is switched off. Details and troubleshooting are on [Connecting Stripe](stripe-connect.md).

## 4. Create an event

From the organiser, select **Create event** and fill in the title, dates, venue and description. The cover image is optional but makes the public page and the event listing far more clickable.

Save the event, then publish it from the same screen. Publishing requires a connected Stripe account.

## 5. Add tickets

Open the **Tickets** tab on the event and select **New ticket**. Give it a title, a price and — if you want a cap — a quantity available. Leave the quantity blank for unlimited.

Add one ticket per type. You can reorder, hide or edit them at any time. See [Tickets](tickets.md).

## 6. Share the event

Once the event is live and listed publicly, it appears at **/events**, on your organiser page at **/o/your-organiser** and on its own page at **/e/your-event**. The slug comes from the event title, so *Demo Conference 2026* becomes `/e/demo-conference-2026`.

## A checklist before you share the link

- Stripe shows **Charges enabled: Yes**.
- The event status badge reads **Live**.
- At least one ticket is **On sale** and not hidden.
- The event is in the future — past events drop off the public listing.
- **Show this event on the public site** is ticked.

:::note[Exploring a demo install]
If your platform was set up with the demo data, sign in with `demo@example.com` and the password `password`. You will see the organiser **Demo Events Ltd**, a draft event **Demo Conference 2026**, and two tickets: **General admission** at £25 and **VIP** at £75. The demo organiser has no Stripe account, so the event cannot be published until you connect one.
:::

If you are the person running the platform rather than an organiser, the [local setup guide](../developer/local-setup.md) covers installation.
