<?php

return [
    'default_currency' => env('EVENTS_CURRENCY', 'GBP'),

    'default_timezone' => env('EVENTS_TIMEZONE', 'Europe/London'),

    'currencies' => [
        'GBP' => ['symbol' => '£', 'decimals' => 2],
        'EUR' => ['symbol' => '€', 'decimals' => 2],
        'USD' => ['symbol' => '$', 'decimals' => 2],
    ],

];
