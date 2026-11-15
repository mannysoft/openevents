---
title: Check-in
sidebar_position: 12
description: Check attendees in at the door by typing a reference or scanning a ticket QR code with a phone camera.
---

The check-in screen is the door tool. It works on a phone, tablet or laptop, and confirms in one step whether a ticket is valid.

## Open the check-in screen

Go to the event and select the **Check-in** tab.

Three counters sit at the top: **Total** (valid tickets for the event), **Checked in**, and **Remaining**. Cancelled and refunded tickets are excluded from all three. Below them are **Manual entry**, **Camera scanner** and the ten **Latest check-ins**.

## Check someone in by reference

1. Type the ticket reference — for example `TKT-4HJ7QX2M9P` — into the box.
2. Select **Check in**, or press Enter.

The box clears and a green panel shows the attendee's name and ticket type. That is everything the door team needs.

References are forgiving:

- Capitals do not matter: `tkt-4hj7qx2m9p` works.
- Spaces are ignored.
- Anything before the last `/` is ignored, so scanning or pasting a full ticket address such as `https://your-site/tickets/TKT-4HJ7QX2M9P` works too.

## Scan with the camera

1. Select **Start camera** and allow the browser to use the camera.
2. Point it at the QR code on the attendee's ticket or phone.
3. The scan checks the attendee in exactly like a typed reference.
4. Select **Stop camera** when you are done, and always stop it when you leave the screen.

The scanner ignores a repeat of the same code within three seconds, so a single QR code cannot check in twice by accident.

:::note
Camera scanning needs a secure connection — HTTPS or localhost — and the browser must support camera access. The scanner loads a small library from the web when you first start it, so keep a data connection. If the camera will not start, type the reference instead; it is just as quick.
:::

## What the messages mean

| Message | Meaning |
| --- | --- |
| Name – Ticket type | Checked in successfully. |
| Name checked in. | Checked in successfully; the ticket has no type recorded. |
| Enter or scan a ticket reference. | The box was submitted empty. |
| No ticket found for "CODE" at this event. | Wrong event, or a mistyped reference. |
| Name – this ticket has been refunded. | Refused. Refunded tickets cannot be admitted. |
| Name – this ticket has been cancelled. | Refused. Restore the ticket on the attendee list if this is a mistake. |
| Name – the order for this ticket is not completed. | Refused. The order was never paid. |
| Name was already checked in at HH:MM. | Refused as a duplicate; check the name before admitting. |
| Could not check in Name. | A last-resort failure. Reload the page and try again. |

## Latest check-ins

The list below shows the ten most recent check-ins for the event with the attendee name, ticket type, reference and time. Use it to spot duplicates at a glance.

:::warning
Only check attendees in on the event's own **Check-in** screen. Tickets for one event will not be found on another event's screen, even if the organiser is the same.
:::

:::tip
Test the camera once before doors open, on the device you will actually use. The platform records which signed-in account checked each attendee in, so use your own login rather than a shared one if you need that record to be meaningful.
:::
