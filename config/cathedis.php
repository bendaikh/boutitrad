<?php

return [
    'api_url' => env('CATHEDIS_API_URL', 'https://api.cathedis.delivery'),
    'webhook_secret' => env('CATHEDIS_WEBHOOK_SECRET'),
    'pickup_city' => env('CATHEDIS_PICKUP_CITY', 'Casablanca'),
    'pack' => env('CATHEDIS_PACK', 'silver'),
    'verify_ssl' => filter_var(
        env('CATHEDIS_VERIFY_SSL', env('APP_ENV') === 'production'),
        FILTER_VALIDATE_BOOLEAN
    ),
    'cities_endpoint' => env('CATHEDIS_CITIES_ENDPOINT', '/ws/rest/com.axelor.apps.base.db.City/search'),
    'cities_limit' => env('CATHEDIS_CITIES_LIMIT', -1),
    'delivery_endpoint' => env(
        'CATHEDIS_DELIVERY_ENDPOINT',
        '/ws/rest/com.tracker.delivery.db.Delivery'
    ),
];
