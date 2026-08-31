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

    'internal_api' => [
        'key' => env('INTERNAL_API_KEY'),
    ],

    'inbound_email' => [
        'webhook_secret' => env('INBOUND_EMAIL_WEBHOOK_SECRET'),
    ],

    'gmail' => [
        'client_id' => env('GOOGLE_OAUTH_CLIENT_ID'),
        'client_secret' => env('GOOGLE_OAUTH_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_OAUTH_REDIRECT_URI'),
        'push_verification_token' => env('GMAIL_PUSH_VERIFICATION_TOKEN'),
        'pubsub_topic' => env('GMAIL_PUBSUB_TOPIC'),
    ],

    'outlook' => [
        'client_id' => env('MICROSOFT_OAUTH_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_OAUTH_CLIENT_SECRET'),
        'tenant' => env('MICROSOFT_OAUTH_TENANT_ID', 'common'),
        'redirect' => env('MICROSOFT_OAUTH_REDIRECT_URI'),
        'webhook_client_state' => env('OUTLOOK_WEBHOOK_CLIENT_STATE'),
        'notification_url' => env('OUTLOOK_NOTIFICATION_URL'),
    ],

];
