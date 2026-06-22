Set-Location $PSScriptRoot
Write-Host "boutitrad (BELDI-MALAKI) -> http://127.0.0.1:8002" -ForegroundColor Green
Write-Host "Connexion: http://127.0.0.1:8002/login" -ForegroundColor Cyan
php artisan serve --host=127.0.0.1 --port=8002
