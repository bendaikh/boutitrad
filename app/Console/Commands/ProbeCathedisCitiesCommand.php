<?php

namespace App\Console\Commands;

use App\Services\CathedisSessionService;
use Illuminate\Console\Command;

class ProbeCathedisCitiesCommand extends Command
{
    protected $signature = 'cathedis:probe-cities';

    protected $description = 'Explore les endpoints Cathedis pour la liste des villes';

    public function handle(CathedisSessionService $session): int
    {
        $baseUrl = rtrim((string) config('cathedis.api_url'), '/');

        if (! $session->isConfigured()) {
            $this->error('Cathedis non configuré.');

            return self::FAILURE;
        }

        if ($session->credentialsConfigured()) {
            $session->authenticate($baseUrl);
        }

        $client = $session->http($baseUrl);

        $getEndpoints = [
            '/cities', '/api/cities', '/ws/cities', '/ws/public/cities',
            '/ws/rest/com.cathedis.db.City/search', '/ws/rest/com.cathedis.db.City/fetch',
        ];

        foreach ($getEndpoints as $endpoint) {
            $this->probe($client, 'GET', $endpoint);
        }

        $postEndpoints = [
            ['/ws/rest/com.cathedis.db.City/search', ['limit' => -1, 'offset' => 0, 'fields' => ['id', 'name', 'code']]],
            ['/ws/rest/com.cathedis.db.City/fetch', ['data' => ['_domain' => null, 'limit' => -1]]],
            ['/ws/rest/com.axelor.apps.base.db.City/search', ['limit' => -1, 'offset' => 0]],
            ['/ws/rest/com.axelor.apps.delivery.db.City/search', ['limit' => -1, 'offset' => 0]],
            ['/ws/rest/com.cathedis.db.DeliveryCity/search', ['limit' => -1, 'offset' => 0]],
            ['/ws/rest/com.cathedis.db.DeliveryCity/fetch', ['limit' => -1]],
            ['/ws/rest/com.cathedis.db.Ville/search', ['limit' => -1, 'offset' => 0]],
            ['/ws/action/com.cathedis.web.City:fetchAll', []],
            ['/ws/action/com.cathedis.web.City:all', []],
            ['/ws/action/com.cathedis.web.DeliveryCity:fetchAll', []],
        ];

        foreach ($postEndpoints as [$endpoint, $payload]) {
            $this->probe($client, 'POST', $endpoint, $payload);
        }

        return self::SUCCESS;
    }

    private function probe($client, string $method, string $endpoint, array $payload = []): void
    {
        try {
            $response = $method === 'POST'
                ? $client->asJson()->post($endpoint, $payload)
                : $client->get($endpoint);

            $status = $response->status();
            $body = substr(preg_replace('/\s+/', ' ', $response->body()), 0, 200);
            $count = 0;

            if ($response->successful()) {
                $json = $response->json();
                $count = $this->countItems($json);
            }

            if ($status !== 404) {
                $this->line("[{$method} {$status}] {$endpoint} count≈{$count} | {$body}");
            }
        } catch (\Throwable $e) {
            $this->line("[ERR {$method}] {$endpoint} → ".$e->getMessage());
        }
    }

    private function countItems(mixed $payload): int
    {
        if (! is_array($payload)) {
            return 0;
        }

        foreach (['data', 'cities', 'records', 'result', 'values'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                return count($payload[$key]);
            }
        }

        if (isset($payload['status']) && $payload['status'] === 0 && isset($payload['data'])) {
            if (is_array($payload['data']) && array_is_list($payload['data'])) {
                return count($payload['data']);
            }
        }

        return array_is_list($payload) ? count($payload) : 0;
    }
}
