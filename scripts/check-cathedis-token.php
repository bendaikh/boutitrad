<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Support\CathedisConfig;

$token = CathedisConfig::apiToken();
$username = CathedisConfig::username();

echo 'token_configure='.(filled($token) ? 'oui' : 'non')."\n";
echo 'auth_mode='.(filled($token) ? 'token' : (CathedisConfig::isConfigured() ? 'login' : 'aucun'))."\n";
echo 'email='.($username ?: '-')."\n";
