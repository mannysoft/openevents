---
title: Connecting Stripe
sidebar_position: 5
description: Connect an organiser to Stripe, read outstanding requirements and understand how payments and fees are handled.
---

Every organiser needs its own Stripe account before it can sell tickets. Stripe collects your business details, verifies your identity and pays the money into your bank account.

## Before you start

Have ready:

- Your business or trading name and address.
- A contact email and phone number.
- Bank account details for payouts.
- Identity documents for the person responsible for the account.

## Connect an account

1. Open **Dashboard → Organisers**.
2. Open your organiser, then select **Stripe** (or **Stripe settings** on the event list).
3. Select **Connect Stripe**.
4. Complete Stripe's onboarding pages and return to the dashboard.

Stripe's onboarding runs on Stripe's own site. When you finish, you come back to the **Stripe Connect** screen and the account status is refreshed automatically.

## Reading the account status

| Row | Meaning |
| --- | --- |
| Account ID | Stripe's identifier for this organiser's connected account. |
| Charges enabled | **Yes** means sales transfer straight to the organiser's account. |
| Details submitted | Whether Stripe has everything it needs from you yet. |
| Onboarded | When the account first became ready, or a dash if not yet. |

Buttons on this card:

- **Connect Stripe** or **Continue onboarding** — opens Stripe's onboarding again. Use this whenever something is outstanding.
- **Refresh status** — re-reads the account from Stripe after you have completed a step elsewhere.
- **Open Stripe dashboard** — only appears once charges are enabled. This is Stripe's own Express dashboard for payouts, balances and documents.

## Outstanding requirements

Stripe lists anything it still needs in two groups:

- **Required now** — Stripe is waiting on this. Usually identity, bank or business details.
- **Required later** — not blocking today, but will be before a deadline.

Each entry shows Stripe's own description, a note when **Stripe is reviewing this**, and any error code in red if Stripe rejected something. When there is nothing left, the card reads *No outstanding requirements. You are all set.*

If Stripe has restricted the account, a red banner explains why. In that case, open Stripe's onboarding and follow its instructions; nothing in the dashboard can clear it for you.

## Expired onboarding links

Onboarding links are single use and expire. If you open an old one, Stripe sends you back to the **Stripe Connect** screen with the message *Your onboarding link expired, please start again.* Select **Continue onboarding** to get a fresh link.

:::warning
Until **Charges enabled** reads **Yes**, the platform takes payments on its own Stripe account and the organiser is settled outside Stripe. Publishing and checkout are not blocked: the event form shows a Stripe reminder so it is clear where the money is going.
:::

## How money moves

- The buyer pays the platform through Stripe.
- The money is transferred immediately to the organiser's connected Stripe account.
- The platform keeps an application fee — 5% by default, or the percentage set on the organiser.

Refunds reverse the transfer and return the platform's fee proportionally. See [Orders and refunds](orders-and-refunds.md) and [Payouts and earnings](payouts-and-earnings.md).

:::tip
After finishing onboarding, always select **Refresh status** before taking payments. Stripe sometimes takes a moment to mark the account as ready.
:::
