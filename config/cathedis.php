<?php

return [
    'enabled' => env('CATHEDIS_ENABLED', false),
    'api_url' => env('CATHEDIS_API_URL', 'https://api.cathedis.delivery'),
    'api_token' => env('CATHEDIS_API_TOKEN'),
    'username' => env('CATHEDIS_USERNAME'),
    'password' => env('CATHEDIS_PASSWORD'),
    'webhook_secret' => env('CATHEDIS_WEBHOOK_SECRET'),
    'pickup_city' => env('CATHEDIS_PICKUP_CITY', 'Casablanca'),
    'pack' => env('CATHEDIS_PACK', 'silver'),
    'verify_ssl' => env('CATHEDIS_VERIFY_SSL', env('APP_ENV') === 'production'),
    'cities_endpoint' => env('CATHEDIS_CITIES_ENDPOINT', '/ws/rest/com.axelor.apps.base.db.City/search'),
    'cities_limit' => env('CATHEDIS_CITIES_LIMIT', -1),
];
