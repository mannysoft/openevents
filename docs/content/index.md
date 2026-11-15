---
id: index
title: Documentation
slug: /
sidebar_position: 1
---

# Events documentation

Events is a small, deliberately bare multi-organiser ticketing platform built on
**Laravel 13** and **Livewire 4**. Organisers sign up, create events and tickets, and take
payments through **their own Stripe account** using Stripe Connect (Express). The platform
keeps a configurable application fee — 5% by default — on each sale.

This site is split into two guides. Pick the one that matches your role.

## User guide

For the people running events.

- [Getting started](user/getting-started.md) — from sign-up to a published event
- [Managing organisers](user/organisers.md) — branding, currency and the platform fee
- [Connecting Stripe](user/stripe-connect.md) — onboarding and requirements
- [Creating events](user/creating-events.md) and [tickets](user/tickets.md)
- [Discount codes](user/discount-codes.md) and [attendee questions](user/questions.md)
- [Orders and refunds](user/orders-and-refunds.md)
- [Attendees](user/attendees.md), [check-in](user/check-in.md) and [messaging](user/messaging.md)
- [Payouts and earnings](user/payouts-and-earnings.md)
- [For attendees](user/for-attendees.md) — what buyers see

## Developer guide

For the people maintaining the code.

- [Local setup](developer/local-setup.md) and [configuration](developer/configuration.md)
- [Architecture](developer/architecture.md) and [domain model](developer/domain-model.md)
- [Money and currency](developer/money-and-currency.md)
- [Order lifecycle](developer/order-lifecycle.md)
- [Stripe Connect](developer/stripe-connect.md) and [payments and webhooks](developer/payments-and-webhooks.md)
- [Livewire components](developer/livewire-components.md) and [routes reference](developer/routes-reference.md)
- [Authorization](developer/authorization.md), [testing](developer/testing.md) and [code style](developer/code-style.md)
- [Mail and notifications](developer/mail-and-notifications.md)
- [Deployment and operations](developer/deployment-and-operations.md) and [extending](developer/extending.md)

## Conventions used here

- Amounts are always shown in the platform's display currency and are stored internally
  as integer **minor units** (pence, cents).
- "The platform" means this application and its Stripe platform account. "The connected
  account" means the Stripe Express account that belongs to an organiser.
- Paths such as `app/Services/OrderService.php` are relative to the repository root.

:::tip[Looking for something specific?]
Use the search box in the navigation bar — the index is built at compile time, so it works
offline and without an Algolia account.
:::
