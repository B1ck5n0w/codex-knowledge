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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        // Never reuse the browser-restricted key here. Laravel sends requests from
        // the server, therefore it needs its own IP-restricted credential.
        'places_key' => env('GOOGLE_PLACES_SERVER_API_KEY'),
        // Hard budget guard: every outbound Places request consumes one unit.
        // Keep this deliberately low for local development; raise it explicitly if needed.
        // Für den lokalen Testbetrieb ausreichend hoch, aber weiterhin mit einer klaren Kostenbremse.
        'places_daily_limit' => (int) env('GOOGLE_PLACES_DAILY_LIMIT', 100),
        'places_cache_minutes' => (int) env('GOOGLE_PLACES_CACHE_MINUTES', 360),
    ],

    'openrouteservice' => [
        'key' => env('OPENROUTESERVICE_API_KEY'),
        'url' => env('OPENROUTESERVICE_URL', 'https://api.openrouteservice.org/v2/directions/cycling-regular/geojson'),
    ],

];
