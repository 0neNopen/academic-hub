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

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN', ''),
        'bot_username' => env('TELEGRAM_BOT_USERNAME', 'AcademicHubBot'),
    ],

    'whatsapp' => [
        'driver' => env('WHATSAPP_DRIVER', 'green_api'), // Opsi gratis: green_api (cloud) atau waha (self-hosted)
        'green_api' => [
            'instance_id' => env('GREEN_API_INSTANCE_ID', ''),
            'api_token' => env('GREEN_API_TOKEN', ''),
        ],
        'waha' => [
            'url' => env('WAHA_API_URL', 'http://localhost:3000'),
            'api_key' => env('WAHA_API_KEY', ''),
        ],
    ],

];
