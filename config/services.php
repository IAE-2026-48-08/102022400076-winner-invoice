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

    'sso' => [
        'base_url' => env('SSO_BASE_URL', 'https://iae-sso.virtualfri.id'),
        'api_key' => env('SSO_API_KEY'),
        'team_id' => env('SSO_TEAM_ID', 'TEAM-166'),
        'nim' => env('SSO_NIM', '102022400076'),
        'timeout' => (int) env('SSO_TIMEOUT', 10),
    ],

    'soap' => [
        'audit_url' => env(
            'SOAP_AUDIT_URL',
            rtrim(env('SSO_BASE_URL', 'https://iae-sso.virtualfri.id'), '/').'/soap/v1/audit'
        ),
    ],

    'rabbitmq' => [
        'driver' => env('RABBITMQ_DRIVER', 'amqp'),
        'host' => env('RABBITMQ_HOST', 'localhost'),
        'port' => (int) env('RABBITMQ_PORT', 5672),
        'user' => env('RABBITMQ_USER', 'guest'),
        'password' => env('RABBITMQ_PASSWORD', 'guest'),
        'queue' => env('RABBITMQ_QUEUE', 'winner_invoice_queue'),
    ],

];
