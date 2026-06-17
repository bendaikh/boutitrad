<?php

namespace App\Services;

use App\Support\CathedisConfig;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CathedisSessionService
{
    private const CACHE_KEY = 'cathedis.session.cookies';

    public function credentialsConfigured(): bool
    {
        return filled(CathedisConfig::username()) && filled(CathedisConfig::password());
    }

    public function tokenConfigured(): bool
    {
        return filled(CathedisConfig::apiToken());
    }

    public function isConfigured(): bool
    {
        return $this->tokenConfigured() || $this->credentialsConfigured();
    }

    public function http(string $baseUrl): PendingRequest
    {
        $baseUrl = rtrim($baseUrl, '/');
        $options = $this->httpOptions($baseUrl);

        if ($this->tokenConfigured()) {
            return Http::withOptions($options)
                ->withToken((string) CathedisConfig::apiToken())
                ->timeout(20)
                ->acceptJson()
                ->baseUrl($baseUrl);
        }

        return Http::withOptions([
            'cookies' => $this->cookieJar($baseUrl, $this->sessionCookies()),
        ] + $options)
            ->timeout(20)
            ->acceptJson()
            ->baseUrl($baseUrl);
    }

    public function authenticate(string $baseUrl): bool
    {
        if ($this->tokenConfigured()) {
            return true;
        }

        if (! $this->credentialsConfigured()) {
            return false;
        }

        $baseUrl = rtrim($baseUrl, '/');

        try {
            $options = $this->httpOptions($baseUrl);
            $jar = $this->cookieJar($baseUrl);

            $loginPage = Http::withOptions(['cookies' => $jar] + $options)
                ->timeout(15)
                ->get($baseUrl.'/login.jsp');

            if (! $loginPage->successful()) {
                return false;
            }

            $response = Http::withOptions(['cookies' => $jar] + $options)
                ->asForm()
                ->timeout(20)
                ->withHeaders($this->csrfHeaders($loginPage->headers()))
                ->post($baseUrl.'/callback', [
                    'username' => CathedisConfig::username(),
                    'password' => CathedisConfig::password(),
                    'rememberMe' => 'rememberMe',
                    'hash_location' => '',
                ]);

            $cookies = $this->cookiesFromJar($jar);

            if (($response->successful() || $response->redirect()) && isset($cookies['JSESSIONID'])) {
                Cache::put(self::CACHE_KEY, $cookies, now()->addHours(2));

                return true;
            }

            Log::warning('Cathedis login rejected', [
                'status' => $response->status(),
                'has_session' => isset($cookies['JSESSIONID']),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Cathedis login failed', ['message' => $e->getMessage()]);
        }

        return false;
    }

    public function clearSession(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, string>
     */
    private function sessionCookies(): array
    {
        $cookies = Cache::get(self::CACHE_KEY, []);

        if ($cookies !== []) {
            return $cookies;
        }

        if ($this->authenticate((string) config('cathedis.api_url'))) {
            return Cache::get(self::CACHE_KEY, []);
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function httpOptions(string $baseUrl): array
    {
        return [
            'verify' => (bool) config('cathedis.verify_ssl', true),
        ];
    }

    /**
     * @param  array<string, string>  $cookies
     */
    private function cookieJar(string $baseUrl, array $cookies = []): CookieJar
    {
        $host = parse_url($baseUrl, PHP_URL_HOST) ?: 'api.cathedis.delivery';

        return CookieJar::fromArray($cookies, $host);
    }

    /**
     * @return array<string, string>
     */
    private function cookiesFromJar(CookieJar $jar): array
    {
        $cookies = [];

        foreach ($jar->toArray() as $cookie) {
            if (! empty($cookie['Name'])) {
                $cookies[$cookie['Name']] = (string) ($cookie['Value'] ?? '');
            }
        }

        return $cookies;
    }

    /**
     * @param  array<string, array<int, string>>  $headers
     * @return array<string, string>
     */
    private function csrfHeaders(array $headers): array
    {
        $csrf = $headers['x-csrf-token'][0] ?? $headers['X-CSRF-TOKEN'][0] ?? null;

        return $csrf ? ['X-CSRF-TOKEN' => $csrf] : [];
    }
}
