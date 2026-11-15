{{-- Plain-text email: values are printed raw because nothing here is rendered as HTML. --}}
@if (count($rows) > 0)
{!! count($rows) !!} paid event booking{!! count($rows) === 1 ? '' : 's' !!} on globalwomanevents.com {!! count($rows) === 1 ? 'has' : 'have' !!} no order.

{!! count($rows) === 1 ? 'The buyer' : 'Each buyer' !!} paid in Stripe but did not come back to the site after paying, so no order, attendee or ticket was created on the site.

What to do: add this buyer to the event manually; do not ask her to rebook. Check the event's attendee list first, in case she completed the booking after this check ran.
@foreach ($rows as $row)

---- {!! $loop->iteration !!} of {!! count($rows) !!} ----
Event:          {!! $row['event_title'] ?? 'unknown' !!} (event {!! $row['event_id'] !!})
Event date:     {!! $row['event_date'] ?? 'unknown' !!}
Club:           {!! $row['club_text'] !!}
Amount:         {!! $row['amount_text'] !!}
Paid at:        {!! $row['paid_utc'] !!} ({!! $row['paid_uk'] !!})
Buyer email:    {!! $row['email'] ?? 'not recorded in Stripe' !!}
@if (! empty($row['name']))
Buyer name:     {!! $row['name'] !!}
@endif
Stripe account: {!! $row['account_text'] !!}
PaymentIntent:  {!! $row['payment_intent'] !!}
Charge:         {!! $row['charge'] ?? 'none' !!}
@if (! empty($row['existing_order']))
Note: this email already has order {!! $row['existing_order'] !!} for this event, so this may be a second payment for the same booking. Check before adding her again.
@endif
@endforeach
@endif
@if (count($loungeRows) > 0)
@if (count($rows) > 0)

@endif
{!! count($loungeRows) !!} paid event booking{!! count($loungeRows) === 1 ? '' : 's' !!} made in the Global Woman Lounge app or website {!! count($loungeRows) === 1 ? 'has' : 'have' !!} no ticket record.

{!! count($loungeRows) === 1 ? 'The buyer' : 'Each buyer' !!} paid in Stripe, but the Lounge never saved the booking, so she is not on the event's ticket list in the Lounge (/manager) and has no ticket in the app. The Lounge saves a booking only when the app or web page that took the payment reports back afterwards; nothing else records it.

What to do: first look for her on the event's ticket list in the Lounge /manager, by email and by name, in case the booking was saved after this check ran (on the website she types her email at Stripe, and it can differ from her Lounge account's). If she is not there, add her to the event by hand and tell her she is booked; do not ask her to pay again. If she cannot come, refund this payment in Stripe instead.
@foreach ($loungeRows as $row)

---- {!! $loop->iteration !!} of {!! count($loungeRows) !!} ----
Booked in:      {!! $row['source_text'] !!}
Event:          {!! $row['event_title'] ?? 'unknown' !!} (event {!! $row['event_id'] !!})
Event date:     {!! $row['event_date'] ?? 'unknown' !!}
Club:           {!! $row['club_text'] !!}
Ticket:         {!! $row['ticket_type'] ?? 'not recorded in Stripe' !!}
@if (! empty($row['bring_friend']))
Bring a friend: yes, so her friend has no place either{!! $row['friend_text'] !== '' ? ' ('.$row['friend_text'].')' : '' !!}
@endif
Amount:         {!! $row['amount_text'] !!}
Paid at:        {!! $row['paid_utc'] !!} ({!! $row['paid_uk'] !!})
Buyer email:    {!! $row['email'] ?? 'not recorded in Stripe' !!}
@if (! empty($row['name']))
Buyer name:     {!! $row['name'] !!}
@endif
Stripe account: {!! $row['account_text'] !!}
PaymentIntent:  {!! $row['payment_intent'] !!}
Charge:         {!! $row['charge'] ?? 'none' !!}
@if (! empty($row['session']))
Checkout:       {!! $row['session'] !!}
@endif
@if (! empty($row['session_error']))
Note: its Stripe checkout session could not be read ({!! $row['session_error'] !!}), so it is not known whether the app or the website took the payment.
@endif
@if (($row['lounge_places'] ?? 0) > 0)
Note: the Lounge already has {!! $row['lounge_places'] !!} place{!! $row['lounge_places'] === 1 ? '' : 's' !!} for this email at this event (paid, free or guest). She may already have been added by hand, or this may be a second payment for one booking: check before adding her again, and refund a duplicate payment.
@endif
@endforeach
@endif
@if (count($failures) > 0)

These Stripe accounts could not be checked, so payments on them are not covered by this alert:
@foreach ($failures as $failure)
@if (! empty($failure['label']))
- {!! $failure['label'] !!}: {!! $failure['error'] !!}
@else
- {!! $failure['account'] === 'platform' ? 'platform account' : $failure['account'] !!}@if (! empty($failure['clubs'])) (club: {!! implode(', ', $failure['clubs']) !!})@endif: {!! $failure['error'] !!}
@endif
@endforeach
@endif

Checked: payments started between {!! $checkedFrom !!} and {!! $checkedUntil !!}. Payments made in the last few minutes are left until the next check, because the buyer may still be returning to the site. Each payment is reported once.

Sent by events:check-missed-payments on globalwomanevents.com.
