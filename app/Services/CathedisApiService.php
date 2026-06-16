<?php

namespace App\Services;

use App\Models\City;
use App\Models\DeliveryPartner;
use Illuminate\Support\Facades\Log;

class CathedisApiService
{
    public function __construct(private CathedisSessionService $session) {}

    /** @var list<string> */
    private array $legacyCityEndpoints = [
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

        $citiesProbe = $this->fetchCitiesFromApi($apiUrl);
        if ($citiesProbe['count'] > 0) {
            return [
                ...$status,
                'ok' => true,
                'message' => 'Connexion Cathedis OK — '.$citiesProbe['count'].' villes disponibles via API.',
                'endpoint' => $citiesProbe['endpoint'],
                'api_cities_count' => $citiesProbe['count'],
            ];
        }

        foreach ($this->legacyCityEndpoints as $endpoint) {
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
                'message' => 'Connexion Cathedis OK (session active). Synchronisez les villes pour mettre à jour la liste.',
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

        $probe = $this->fetchCitiesFromApi($apiUrl);
        if ($probe['items'] !== []) {
            return $this->persistCities($probe['items']);
        }

        foreach ($this->legacyCityEndpoints as $endpoint) {
            try {
                $response = $this->session->http($apiUrl)->get($endpoint);

                if (! $response->successful()) {
                    continue;
                }

                $items = $this->normalizeCityPayload($response->json());

                if ($items !== []) {
                    return $this->persistCities($items);
                }
            } catch (\Throwable $e) {
                Log::warning('Cathedis city sync endpoint failed', [
                    'endpoint' => $endpoint,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        Log::warning('Cathedis city sync: API indisponible, utilisation de la liste par défaut (32 villes).');

        $this->seedDefaults();

        return City::query()->count();
    }

    /**
     * @return array{endpoint: string, count: int, items: list<array<string, mixed>>}
     */
    public function fetchCitiesFromApi(string $apiUrl): array
    {
        $endpoint = (string) config('cathedis.cities_endpoint', '/ws/rest/com.axelor.apps.base.db.City/search');

        try {
            $response = $this->session->http($apiUrl)
                ->asJson()
                ->post($endpoint, [
                    'limit' => (int) config('cathedis.cities_limit', -1),
                    'offset' => 0,
                    'fields' => ['id', 'name', 'displayName'],
                ]);

            if (! $response->successful()) {
                return ['endpoint' => $endpoint, 'count' => 0, 'items' => []];
            }

            $items = $this->normalizeCityPayload($response->json());

            return [
                'endpoint' => $endpoint,
                'count' => count($items),
                'items' => $items,
            ];
        } catch (\Throwable $e) {
            Log::warning('Cathedis Axelor city fetch failed', [
                'endpoint' => $endpoint,
                'message' => $e->getMessage(),
            ]);

            return ['endpoint' => $endpoint, 'count' => 0, 'items' => []];
        }
    }

    public function seedDefaults(): void
    {
        (new \Database\Seeders\CathedisCitySeeder)->run();
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function persistCities(array $items): int
    {
        $synced = 0;

        foreach ($items as $item) {
            if (empty($item['name'])) {
                continue;
            }

            City::upsertFromPayload($item);
            $synced++;
        }

        return $synced;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizeCityPayload(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        if (isset($payload['status']) && (int) $payload['status'] === 0 && isset($payload['data']) && is_array($payload['data'])) {
            $payload = $payload['data'];
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

        return array_values(array_filter(array_map(function ($item) {
            if (! is_array($item)) {
                $name = trim((string) $item);

                return $name === '' ? null : ['name' => $name];
            }

            $name = trim((string) ($item['displayName'] ?? $item['name'] ?? $item['label'] ?? $item['city'] ?? ''));

            if ($name === '') {
                return null;
            }

            return [
                'name' => $name,
                'cathedis_code' => $item['id'] ?? $item['code'] ?? $item['city_id'] ?? null,
                'zone' => $item['zone'] ?? null,
            ];
        }, $payload)));
    }

    private function apiUrl(?DeliveryPartner $partner): string
    {
        return $partner?->api_url ?: (string) config('cathedis.api_url');
    }
}
