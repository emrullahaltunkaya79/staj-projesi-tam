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

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // E-ticaret entegrasyonu
    'ecommerce' => [
        'webhook_url' => env('ECOMMERCE_WEBHOOK_URL'),
        'webhook_secret' => env('ECOMMERCE_WEBHOOK_SECRET', 'change-me'),
    ],

    // Casper Network (CSPR) kripto ödeme
    'casper' => [
        'network' => env('CASPER_NETWORK', 'testnet'),
        'receiving_address' => env('CSPR_RECEIVING_ADDRESS'),
        'rpc_url' => env('CASPER_RPC_URL', 'https://rpc.testnet.cspr.network'),
        'rate' => env('CSPR_RATE', '0.1'),
    ],
];