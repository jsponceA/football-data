<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    // captcha de Cloudflare; sin secreto queda apagado (útil en local) y el formulario no lo pide
    'turnstile' => [
        'secret' => env('TURNSTILE_SECRET'),
        'verify_url' => env('TURNSTILE_VERIFY_URL', 'https://challenges.cloudflare.com/turnstile/v0/siteverify'),
        'timeout' => (int) env('TURNSTILE_TIMEOUT', 5),
    ],

    'football_data' => [
        'base_url' => env('FOOTBALL_DATA_URL', 'https://api.football-data.org/v4/'),
        'token' => env('FOOTBALL_DATA_TOKEN'),
        // el plan gratis permite 10 por minuto; uso 9 para dejar margen
        'rate_limit' => (int) env('FOOTBALL_DATA_RATE_LIMIT', 9),
        'timeout' => (int) env('FOOTBALL_DATA_TIMEOUT', 10),
        'fresh_hours' => (int) env('FOOTBALL_DATA_FRESH_HOURS', 24),
    ],

];
