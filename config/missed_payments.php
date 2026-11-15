<?php

/*
 * events:check-missed-payments
 *
 * An order is only written when the buyer's browser comes back to the checkout success page.
 * A buyer who pays and never comes back is charged with no order and no ticket. The command
 * looks for those payments in Stripe and tells a person, so she can be added by hand.
 */
return [

    // Who is told. Comma-separated for more than one address.
    'alert_to' => env('MISSED_PAYMENT_ALERT_TO', 'tech@globalwomanclub.com'),

    // How far back each run looks, in hours. Every payment in the window is checked on every run.
    'lookback_hours' => (int) env('MISSED_PAYMENT_LOOKBACK_HOURS', 48),

    // A payment younger than this is left alone: the buyer may still be on her way back.
    'grace_minutes' => (int) env('MISSED_PAYMENT_GRACE_MINUTES', 15),

    // How long a payment is remembered as already reported. Must be longer than the lookback.
    'remember_days' => (int) env('MISSED_PAYMENT_REMEMBER_DAYS', 30),

];
