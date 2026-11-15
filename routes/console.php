<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Schedule::command('event:agenda')->everyFifteenMinutes();

Schedule::command('events:slug')->everyFifteenMinutes();

Schedule::command('events:update-date')->everyFiveMinutes();

Schedule::command('orders:sync')->everyFiveMinutes();

Schedule::command('stripe:commission-transfer')->everyFiveMinutes();

// Paid in Stripe but never came back to the site, so no order: tell tech (alert only).
Schedule::command('events:check-missed-payments')->everyTenMinutes()->withoutOverlapping();

Schedule::command('update_exchange_rates')->daily();

// Schedule::command('send:visitor-email')->everyMinute();

Schedule::command('send:scheduled-email')
    ->everyTwoMinutes()
    ->timezone('Europe/London')
    ->between('8:00', '22:00');

// Generate and send email for event attendees
Schedule::command('send:attendees-email')
    ->hourly()
    ->timezone('Europe/London')
    ->between('8:00', '22:00');

Schedule::command('send:email-now')
    ->everyFifteenMinutes()
    ->timezone('Europe/London')
    ->between('8:00', '22:00');

Schedule::command('purchased:scheduled-delete')->hourly();

// Schedule::command('send:scheduled-email')
//     ->hourly()->timezone('Europe/London');
//     // ->between('8:00', '19:00');
