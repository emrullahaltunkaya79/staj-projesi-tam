<?php

return [
    'name' => env('BACKOFFICE_NAME', 'Demo Ticaret'),
    'base_url' => env('BACKOFFICE_URL', 'http://127.0.0.1:8000/api/v1'),
    'token' => env('BACKOFFICE_TOKEN', ''),
    'webhook_secret' => env('BACKOFFICE_WEBHOOK_SECRET', ''),
    'timeout' => 15,
];
