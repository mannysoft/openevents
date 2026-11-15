---
title: Mail and notifications
sidebar_position: 16
description: The three mailables, when they are queued, and how bulk attendee messaging works.
---

The application sends transactional mail only. There are no notification classes, no
broadcast channels and no in-app inbox; the `Notifiable` trait on `App\Models\User` is unused.

## Mailables

All live in `app/Mail` and render Markdown templates from `resources/views/emails`.

| Mailable | Constructor | Template | Sent when |
| --- | --- | --- | --- |
| `OrderConfirmation` | `Order $order` | `emails.order-confirmation` | First successful `payment_intent.succeeded` for an order |
| `TicketIssued` | `Attendee $attendee` | `emails.ticket-issued` | An organiser presses "Resend ticket" on the attendee screen |
| `AttendeeMessage` | `string $subjectLine, string $body` | `emails.attendee-message` | Inside the `SendAttendeeMessages` job |

### Order confirmation

`StripeWebhookController::paymentSucceeded` records whether the order was already completed,
calls `OrderService::complete`, and only when it was not completed queues:

```php
Mail::to($completed->email)->queue(
    new OrderConfirmation($completed->load(['event.organiser', 'attendees.ticket']))
);
```

The guard is what makes webhook retries safe: a duplicate event completes the order
idempotently but does not send a second email. The template lists every attendee reference,
formats the start date in the event timezone and renders the venue when present.

### Ticket issued

`Admin\Attendees\Index::resendTicket` validates that the attendee has an email, then queues:

```php
Mail::to($attendee->email)->queue(new TicketIssued($attendee));
```

This is the only trigger. Buying a ticket does **not** send a per-ticket email; the buyer gets
the order confirmation and can open each ticket page from there. `TicketIssued` links to both
the HTML ticket page and the PDF download.

### Attendee message

`AttendeeMessage` has no Eloquent model. It receives a subject and a plain-text body; the
template escapes the body and converts newlines with `nl2br`. It does not include event
branding or an unsubscribe link.

## Bulk messaging

`App\Livewire\Admin\Messaging\Index::send` validates the subject (max 150), body (max 5000)
and recipient filter, builds a map of `email => name`, and dispatches
`App\Jobs\SendAttendeeMessages`:

```php
SendAttendeeMessages::dispatch(
    $this->event->id,
    $this->subject,
    $this->body,
    $recipients,
);
```

The job implements `ShouldQueue` and, in `handle()`, walks the recipients in chunks of 50 and
calls `Mail::to($email, $name ?: null)->send(new AttendeeMessage(...))`. The mail is sent
synchronously inside the job, so the job's runtime grows with the recipient count; there is
no per-message job and no rate limiting.

The component then writes an `App\Models\MessageLog` row with `event_id`, `user_id`,
`subject`, `body`, `recipients_count` and `sent_at`. The log is written immediately after
dispatch, so it records the attempt, not delivery. Recipient filters are `all`, `ticket`,
`checked_in` and `not_checked_in`; recipients are non-cancelled attendees with an email on a
completed order, de-duplicated case-insensitively by address.

## Queue requirements

Both queued mailables and the job need a worker. In development the default
`QUEUE_CONNECTION=database` still requires one:

```bash
php artisan queue:work
```

In tests `QUEUE_CONNECTION=sync`, so queued mailables execute inline and can be asserted with
`Mail::assertQueued(OrderConfirmation::class)`. `MAIL_MAILER=array` captures the messages
instead of sending them.

If no worker runs, `OrderConfirmation`, `TicketIssued` and every attendee message stay in the
`jobs` table indefinitely. See [deployment and operations](deployment-and-operations.md).

## Configuration

| Setting | Default | Effect |
| --- | --- | --- |
| `MAIL_MAILER` | `log` | `log` writes full messages to the Laravel log |
| `MAIL_HOST`, `MAIL_PORT` | `127.0.0.1`, `2525` | SMTP target |
| `MAIL_USERNAME`, `MAIL_PASSWORD` | `null` | SMTP credentials |
| `MAIL_FROM_ADDRESS` | `hello@example.com` | Envelope sender |
| `MAIL_FROM_NAME` | `${APP_NAME}` | Sender name |

For production use `smtp` or one of the transports already configured in `config/mail.php`
(`ses`, `postmark`, `resend`) and set the matching credentials from `config/services.php`.

:::tip[From addresses]
`MAIL_FROM_ADDRESS` must be verified with your provider. Organiser branding appears in the
body and sign-off, not in the `From` header, so every message is sent from the platform
address.
:::

## Template conventions

- Markdown mailables (`markdown: 'emails....'`) are rendered with Laravel's mail components.
  `order-confirmation` uses the `@component('mail::message')` syntax; the other two use
  `<x-mail::message>` / `<x-mail::button>`. Both are supported.
- Templates link with `route()`, so `APP_URL` must be correct in production or links will
  point at localhost.
- No queue name, delay or `afterCommit` is configured on any mailable, so mail uses the
  default queue and connection.
