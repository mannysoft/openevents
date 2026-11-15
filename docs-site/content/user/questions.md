---
title: Attendee questions
sidebar_position: 9
description: Ask attendees extra questions at checkout, such as dietary needs or a session choice, and make them required.
---

Questions let you collect information you need beyond a name and email — meal choices, accessibility needs, a company name, or a workshop selection. They are asked on the details step of checkout, once per attendee.

## Open the questions screen

Go to the event and select the **Questions** tab. The table lists each question with its type, options, whether it is required, what it applies to, how many answers it has, and its position.

Select **New question** to add one.

## Fields

| Field | Required | Notes |
| --- | --- | --- |
| Title | Yes | The question as the buyer reads it, for example *Any dietary requirements?* |
| Type | Yes | **Text**, **Select** or **Checkbox**. |
| Applies to | Yes | **Attendee** or **Order**. Records what the answer describes. |
| Options | For select and checkbox | Comma separated, for example *Small, Medium, Large*. |
| Required | — | Ticked questions must be answered before checkout can continue. |
| Sort order | Yes | Controls the order questions appear in. Lower numbers come first. |

Select **Create question** or **Save changes**.

:::note
A text question shows a single-line box. A select question shows a dropdown built from your options. A checkbox question shows a single *Yes* box, which suits consent-style questions — the options list is still required when you save it, but buyers only see the one box.
:::

## Types at a glance

| Type | What the buyer sees | Options needed |
| --- | --- | --- |
| Text | A single-line answer box | No |
| Select | A dropdown with a *Choose…* placeholder | Yes, at least one |
| Checkbox | A *Yes* checkbox | Yes, at least one |

If you save a select or checkbox question without options, it is rejected with *Add at least one option for a select or checkbox question.*

## Required questions

Ticking **Required** means the answer is validated at checkout: the buyer cannot pay until every required question has been answered for every attendee on the order. Optional questions can be left blank, and blank answers are simply not stored.

:::warning
Turning on **Required** for a question on an event that is already selling affects new checkouts immediately. Only make a question required if the answer is genuinely needed for every attendee.
:::

## Reordering and deleting

- **↑** and **↓** move a question up or down the list and update its sort order.
- **Edit** reopens the full form.
- **Delete** removes the question after a confirmation prompt.

:::caution
Deleting a question also deletes the answers already collected for it. If you only want to stop asking, consider making it optional instead.
:::

:::tip
Keep the list short. Every question adds friction at checkout, and every required question is another reason for a buyer to abandon the order.
:::
