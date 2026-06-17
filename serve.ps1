Set-Location $PSScriptRoot
Write-Host "boutitrad -> http://127.0.0.1:8001" -ForegroundColor Green
php artisan serve --host=127.0.0.1 --port=8001
