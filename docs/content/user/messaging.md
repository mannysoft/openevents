---
title: Messaging attendees
sidebar_position: 13
description: Email attendees of an event, choose who receives the message, and review a log of everything sent.
---

Messaging lets you reach the people who have bought tickets — for a venue change, joining instructions or a thank-you afterwards.

## Open the messaging screen

Go to the event and select the **Messages** tab. The **Compose message** form is at the top and **Recent messages** below it.

## Compose a message

| Field | Notes |
| --- | --- |
| Subject | Required, up to 150 characters. This becomes the email subject line. |
| Message | Required, up to 5,000 characters. Plain text; line breaks are preserved. |
| Recipients | All attendees of completed orders, by ticket type, checked in only, or not checked in. |
| Ticket | Only shown when **By ticket type** is selected. Choose which ticket to target. |

Under the recipient selector a panel shows how many people will receive the message and the first few email addresses as a sample, or *No attendees match this filter yet.*

Select **Queue message**. The message is accepted for everyone matching the filter and a confirmation message reports how many attendees it was queued for.

## Who receives a message

- Only attendees on **completed** orders. Unpaid reservations and cancelled orders are excluded.
- Attendees marked cancelled are excluded, even if their order was paid.
- Only attendees with an email address are included.
- If several tickets share the same email address, that address receives the message once.

:::note
**Checked in only** and **Not checked in** are useful on the day: send the joining instructions to those not yet arrived, and a thank-you to those who came.
:::

## Messages are sent in the background

Selecting **Queue message** does not send the emails immediately. The platform hands them to a background process, which then sends one email per recipient. On a busy event there may be a short delay before the first email arrives.

:::warning
Email delivery depends on a background worker running on the server that hosts the platform. If messages never arrive for any event, ask whoever runs the platform to check that the worker and the outgoing mail settings are working — the [developer guide](../developer/index.md) covers both.
:::

## Recent messages

The table below the form lists the ten most recent messages for the event with the **Subject**, the number of **Recipients**, who it was **Sent by**, and when it was **Sent at**. The recipient count is recorded when you queue the message.

:::tip
Send yourself a test by messaging a small ticket type first, such as a VIP tier, before emailing the whole list.
:::
