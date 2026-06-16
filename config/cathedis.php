<?php

return [
    'enabled' => env('CATHEDIS_ENABLED', false),
    'api_url' => env('CATHEDIS_API_URL', 'https://api.cathedis.ma'),
    'api_token' => env('CATHEDIS_API_TOKEN'),
    'webhook_secret' => env('CATHEDIS_WEBHOOK_SECRET'),
];
