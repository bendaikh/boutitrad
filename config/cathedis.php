<?php

return [
    'enabled' => filter_var(env('CATHEDIS_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    'api_url' => env('CATHEDIS_API_URL', 'https://api.cathedis.delivery'),
    'api_token' => env('CATHEDIS_API_TOKEN'),
    'username' => env('CATHEDIS_USERNAME'),
    'password' => env('CATHEDIS_PASSWORD'),
    'webhook_secret' => env('CATHEDIS_WEBHOOK_SECRET'),
    'pickup_city' => env('CATHEDIS_PICKUP_CITY', 'Casablanca'),
    'pack' => env('CATHEDIS_PACK', 'silver'),
    'verify_ssl' => filter_var(
        env('CATHEDIS_VERIFY_SSL', env('APP_ENV') === 'production'),
        FILTER_VALIDATE_BOOLEAN
    ),
    'cities_endpoint' => env('CATHEDIS_CITIES_ENDPOINT', '/ws/rest/com.axelor.apps.base.db.City/search'),
    'cities_limit' => env('CATHEDIS_CITIES_LIMIT', -1),
    'store_id' => env('CATHEDIS_STORE_ID', '23055'),
    'default_sector_id' => env('CATHEDIS_DEFAULT_SECTOR_ID', '2766'),
    'default_sector_name' => env('CATHEDIS_DEFAULT_SECTOR_NAME', 'Autre'),
    'payment_type_id' => env('CATHEDIS_PAYMENT_TYPE_ID', '1'),
    'delivery_type_id' => env('CATHEDIS_DELIVERY_TYPE_ID', '1'),
    'delivery_status_id' => env('CATHEDIS_DELIVERY_STATUS_ID', '1'),
    'delivery_status_code' => env('CATHEDIS_DELIVERY_STATUS_CODE', 'En Attente Ramassage'),
    'allow_opening' => filter_var(env('CATHEDIS_ALLOW_OPENING', false), FILTER_VALIDATE_BOOLEAN),
    'range_weight' => env('CATHEDIS_RANGE_WEIGHT', 'ONE_FIVE'),
    'shipping_method' => env('CATHEDIS_SHIPPING_METHOD', 'LAD'),
    'type_delivery' => env('CATHEDIS_TYPE_DELIVERY', 'NORMAL'),
    'delivery_endpoint' => env(
        'CATHEDIS_DELIVERY_ENDPOINT',
        '/ws/rest/com.tracker.delivery.db.Delivery'
    ),
];
