---
title: Attendees
sidebar_position: 11
description: Search and filter attendees, fix names and emails, cancel or restore tickets, resend tickets and export lists.
---

The attendee list is the guest list for an event. Each attendee is one ticket with its own reference and QR code.

## Open the attendee list

Go to the event and select the **Attendees** tab.

Only attendees whose order is **Completed** appear. Reserved (unpaid) orders are not listed — so if someone has just paid and is missing, give the payment a moment to confirm.

## Summary counts

Four cards at the top show **Total attendees**, **Checked in**, **Cancelled** and **Refunded** for the event. They always cover the whole event, not the current filter.

## Filters

| Filter | What it does |
| --- | --- |
| Search | Matches first name, last name, email or ticket reference. |
| Ticket | Limits the list to one ticket type. |
| Status | Active, Checked in, Cancelled or Refunded. **Active** means not cancelled, not refunded and not yet checked in. |

The number of matching attendees is shown under the filters, along with **Clear filters**.

## Columns and actions

The table shows **Reference**, **Name**, **Email**, **Ticket**, the **Order** reference, **Status** and the **Checked in** time. On each row:

- **Edit** — opens a small form for first name, last name and email. First name and email are required. Use this when someone bought under a nickname or with a typo in their address.
- **Ticket** — opens the attendee's public ticket page with its QR code, ready to show at the door.
- **Resend** — sends the ticket email again to the address on the row, after a confirmation prompt.
- **Cancel** — marks the ticket cancelled after a confirmation prompt. A cancelled ticket cannot be checked in but still appears in the list.
- **Restore** — appears on cancelled tickets and makes them valid again.

:::warning
Cancelling an attendee does not refund them and does not return the ticket to sale. If money needs to go back, refund the order instead — see [Orders and refunds](orders-and-refunds.md).
:::

## Exporting the list

Two buttons at the top of the screen download the attendee list:

- **Export CSV** — a plain comma-separated file, suitable for spreadsheets or a door list.
- **Export XLSX** — an Excel workbook.

Exports always contain every attendee of the event on a completed order, regardless of the filters on screen. The columns are **Reference**, **Name**, **Email**, **Ticket**, **Order**, **Status**, **Refunded** and **Checked in**.

:::tip
Export a copy of the list before the event and keep it on a second device. It is a useful fallback if the venue wifi makes the dashboard slow at the door.
:::
