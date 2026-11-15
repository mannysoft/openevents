<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'stripe' => [
        'model' => App\Models\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'transfer_secret' => env('STRIPE_TRANSFER_SECRET'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'open_exchange_rates' => [
        'api_url' => 'https://openexchangerates.org/api/',
        'app_id' => 'b36a2becb13840ca9897a28c02644b2f',
    ],

    // Member sign-in through the Lobby (every "Login Now" button): off unless set, while the
    // Lobby is offline. Members book with their membership email; their price comes from the Lounge.
    'lobby_sign_in' => [
        'enabled' => (bool) env('LOBBY_SIGN_IN', false),
    ],

    // Paying for an event with Lobby points: off unless set. See App\Services\PointsRedemptionGuard.
    'lobby_points' => [
        'redemption' => (bool) env('LOBBY_POINTS_REDEMPTION', false),
    ],

    'lobby_app' => [
        'server_key' => env('LOBBY_APP_SERVER_KEY', '4025df01045c1336163159ea5577b4d0'),
        'app_id' => env('LOBBY_APP_ID', '77d26d549bb4fa49cc62'),
        'app_secret' => env('LOBBY_APP_SECRET', 'e825e001fedc894e606a064bbb7e065b16311ff'),
        'login_url' => env('LOBBY_APP_LOGIN_URL', 'https://lobby.globalwomanclub.com/index.php?link1=login-event'),
    ],

];
