---
title: Welcome to Events
sidebar_position: 1
description: An introduction to the Events ticketing platform for organisers, and a pointer to the buyer guide.
---

Events is a small ticketing platform for selling tickets to your own events. You create an event, add ticket types, share a link, and take payment through your own Stripe account. This guide walks through each screen in plain language.

## Who this guide is for

Most of these pages are written for **organisers** — the people who create events and sell tickets. If you are buying a ticket instead, start with [Buying a ticket](for-attendees.md).

You do not need any technical knowledge to use the dashboard. Everything is done in the browser.

## What you can do

- Run more than one organiser from a single sign-in.
- Create events with dates, a venue, a cover image and a description.
- Sell several ticket types per event, with prices, quantity limits and sales windows.
- Offer discount codes and ask attendees your own questions at checkout.
- Take card payments through your own Stripe account.
- Track orders, refund all or part of an order, and export attendee lists.
- Check attendees in at the door with a phone camera or a typed reference.
- Email attendees and review how much each event has earned.

## How the pieces fit together

| Piece | What it means |
| --- | --- |
| Organiser | The brand that owns events, branding and payout details. Every organiser has its own Stripe account. |
| Event | A dated occasion with a venue, description and a public page. Events start as drafts. |
| Ticket | A type of admission for an event — for example General admission or VIP — with its own price and quantity. |
| Order | One purchase, identified by a reference such as `ORD-8F3K2Q1Z`. An order holds one or more tickets. |
| Attendee | A named person on an order, with their own reference such as `TKT-4HJ7QX2M9P`. Each attendee gets a ticket with a QR code. |

## How money works

Buyers pay through Stripe. The payment is taken by the platform, the money is transferred straight to the organiser's connected Stripe account, and the platform keeps a small application fee — 5% by default, adjustable per organiser.

You never handle card details yourself, and the money settles in your own Stripe balance. See [Payouts and earnings](payouts-and-earnings.md) for the reporting side.

## Where to start

1. [Getting started](getting-started.md) — create an account and get your first event live.
2. [Connecting Stripe](stripe-connect.md) — required before you can publish and sell.
3. [Creating events](creating-events.md) and [Tickets](tickets.md) — build what you are selling.
4. [Orders and refunds](orders-and-refunds.md), [Attendees](attendees.md) and [Check-in](check-in.md) — run the event itself.

:::tip
References are the fastest way to find anything. An order reference starts with `ORD-` and an attendee reference starts with `TKT-`, and both can be pasted into the search boxes in the dashboard.
:::
