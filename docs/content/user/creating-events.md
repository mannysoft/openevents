---
title: Creating events
sidebar_position: 6
description: Create an event, set its schedule and venue, control visibility, then publish it or return to draft.
---

Events live inside an organiser. An event starts as a draft, and only becomes visible to the public once it is published, listed publicly and still in the future.

## Open the event list

Go to **Dashboard → Organisers → your organiser → Events**. The list shows every event with its dates, status, tickets sold and revenue, plus links to **Edit**, **Tickets**, **Orders** and — for live, publicly listed events — **View**.

Select **Create event** to start a new one.

## Event fields

| Field | Required | Notes |
| --- | --- | --- |
| Title | Yes | Used for the public page heading and to generate the web address. |
| Description | No | Free text; line breaks are kept. |
| Cover image | No | PNG or JPG up to 4 MB. Shown on the event page and the public listing. |
| Starts | Yes | Date and time, entered in the event timezone. |
| Ends | No | Must not be earlier than the start. |
| Timezone | Yes | Defaults to the organiser's timezone. The times above are read in this timezone. |
| Venue name | No | For example *The Old Library*. |
| Address | No | First line of the street address. |
| City | No | Shown on the public listing. |
| State / region | No | Optional. |
| Postcode | No | Optional. |
| Country code | No | Two letters, for example GB. Defaults to GB. |
| Maximum attendees | No | Overall capacity for the event. Leave blank for unlimited. |
| Show this event on the public site | — | Ticked by default. Untick to keep the event off the public listing. |

Select **Create event** to save. You stay on the event screen, where the tabs for **Tickets**, **Orders**, **Attendees**, **Check-in**, **Discounts**, **Questions** and **Messages** become available.

## Times and timezones

Enter the start and end times as they should appear locally to attendees, and pick the timezone for that venue. The dashboard converts them for storage and displays them again in the event's timezone, so a 09:30 start stays 09:30 for everyone reading the page.

:::note
If you run events in several cities, set the timezone per event rather than relying on the organiser default.
:::

## The event's web address

Each event gets a slug generated from its title — *Demo Conference 2026* becomes `/e/demo-conference-2026`. Slugs are unique per organiser, so a second event with the same title becomes `/e/demo-conference-2026-2`. The address is fixed once created, so choose the title before you share the link.

## Publishing and unpublishing

- **Publish** sets the event live. It is refused with the message *Connect Stripe for this organiser before publishing* while the organiser's Stripe account is not ready.
- **Unpublish** returns a live event to draft after a confirmation prompt. Tickets and orders are untouched; the public page simply stops working.

A published event only appears publicly when **Show this event on the public site** is ticked and the start date is still in the future. Draft and unlisted events return a not-found page at their address; past events stay reachable for anyone with the link but drop out of the public listings.

## Deleting an event

Select **Delete event** at the bottom of the form and confirm. The event disappears from the dashboard and its public page stops working. This is intended for events created by mistake — check [Orders and refunds](orders-and-refunds.md) before deleting anything that has sold tickets.

:::tip
Publish once the Stripe status on the organiser shows **Stripe connected**, add at least one ticket that is on sale, then open the public page from **View public page** to check it looks right before sharing it.
:::
