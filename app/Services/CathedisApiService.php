<?php

namespace App\Services;

use App\Models\City;
use App\Models\DeliveryPartner;
use Illuminate\Support\Facades\Log;

class CathedisApiService
{
    public function __construct(private CathedisSessionService $session) {}

    /** @var list<string> */
    private array $cityEndpoints = [
        '/cities',
        '/api/cities',
        '/api/v1/cities',
        '/villes',
        '/api/villes',
    ];

    public function connectionStatus(?DeliveryPartner $partner = null): array
    {
        $partner ??= DeliveryPartner::defaultPartner();
        $apiUrl = $this->apiUrl($partner);
        $enabled = (bool) config('cathedis.enabled');
        $configured = $this->session->isConfigured();
        $authMode = $this->session->tokenConfigured() ? 'token' : ($this->session->credentialsConfigured() ? 'login' : null);

        return [
            'enabled' => $enabled,
            'configured' => $configured,
            'auth_mode' => $authMode,
            'api_url' => $apiUrl,
            'partner' => $partner?->name,
            'cities_count' => City::query()->where('is_active', true)->count(),
            'ready' => $enabled && $configured && $partner !== null,
        ];
    }

    public function testConnection(?DeliveryPartner $partner = null): array
    {
        $partner ??= DeliveryPartner::defaultPartner();
        $status = $this->connectionStatus($partner);

        if (! $status['ready']) {
            return [
                ...$status,
                'ok' => false,
                'message' => 'API Cathedis non configurée. Ajoutez CATHEDIS_USERNAME + CATHEDIS_PASSWORD (ou CATHEDIS_API_TOKEN) dans .env.',
            ];
        }

        $apiUrl = rtrim((string) $status['api_url'], '/');

        if ($status['auth_mode'] === 'login' && ! $this->session->authenticate($apiUrl)) {
            return [
                ...$status,
                'ok' => false,
                'message' => 'Connexion Cathedis refusée. Vérifiez CATHEDIS_USERNAME et CATHEDIS_PASSWORD (même identifiants que https://api.cathedis.delivery).',
            ];
        }

        foreach ($this->cityEndpoints as $endpoint) {
            try {
                $response = $this->session->http($apiUrl)->get($endpoint);

                if ($response->successful()) {
                    return [
                        ...$status,
                        'ok' => true,
                        'message' => 'Connexion Cathedis OK ('.$endpoint.').',
                        'endpoint' => $endpoint,
                    ];
                }
            } catch (\Throwable $e) {
                Log::debug('Cathedis test endpoint failed', ['endpoint' => $endpoint, 'error' => $e->getMessage()]);
            }
        }

        if ($status['auth_mode'] === 'login') {
            return [
                ...$status,
                'ok' => true,
                'message' => 'Connexion Cathedis OK (session active sur api.cathedis.delivery).',
            ];
        }

        return [
            ...$status,
            'ok' => false,
            'message' => 'Impossible de joindre l\'API Cathedis. Vérifiez l\'URL et les identifiants.',
        ];
    }

    public function syncCities(?DeliveryPartner $partner = null): int
    {
        $partner ??= DeliveryPartner::defaultPartner();
        $apiUrl = rtrim($this->apiUrl($partner), '/');

        if (! config('cathedis.enabled') || ! $this->session->isConfigured()) {
            $this->seedDefaults();

            return City::query()->count();
        }

        if ($this->session->credentialsConfigured()) {
            $this->session->authenticate($apiUrl);
        }

        $synced = 0;

        foreach ($this->cityEndpoints as $endpoint) {
            try {
                $response = $this->session->http($apiUrl)->get($endpoint);

                if (! $response->successful()) {
                    continue;
                }

                $items = $this->normalizeCityPayload($response->json());

                if ($items === []) {
                    continue;
                }

                foreach ($items as $item) {
                    if (empty($item['name'])) {
                        continue;
                    }

                    City::upsertFromPayload($item);
                    $synced++;
                }

                if ($synced > 0) {
                    return $synced;
                }
            } catch (\Throwable $e) {
                Log::warning('Cathedis city sync endpoint failed', [
                    'endpoint' => $endpoint,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $this->seedDefaults();

        return City::query()->count();
    }

    public function seedDefaults(): void
    {
        (new \Database\Seeders\CathedisCitySeeder)->run();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizeCityPayload(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        if (isset($payload['data']) && is_array($payload['data'])) {
            $payload = $payload['data'];
        }

        if (isset($payload['cities']) && is_array($payload['cities'])) {
            $payload = $payload['cities'];
        }

        if ($payload === [] || ! array_is_list($payload)) {
            return [];
        }

        return array_map(function ($item) {
            if (! is_array($item)) {
                return ['name' => (string) $item];
            }

            return [
                'name' => $item['name'] ?? $item['label'] ?? $item['city'] ?? '',
                'cathedis_code' => $item['id'] ?? $item['code'] ?? $item['city_id'] ?? null,
                'zone' => $item['zone'] ?? null,
            ];
        }, $payload);
    }

    private function apiUrl(?DeliveryPartner $partner): string
    {
        return $partner?->api_url ?: (string) config('cathedis.api_url');
    }
}
