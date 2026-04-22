<?php

namespace App\Services\ComprasGov;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ComprasGovApiClient
{
    /**
     * Perform a GET request to the Compras.gov.br API.
     * Results are cached for 1 hour to avoid rate-limiting.
     */
    public function get(string $path, array $query = []): array
    {
        $cacheKey = 'compras_gov:' . md5($path . serialize($query));

        return Cache::remember($cacheKey, now()->addHour(), function () use ($path, $query): array {
            try {
                $response = $this->client()
                    ->get($path, $query);

                if ($response->failed()) {
                    Log::warning('ComprasGov API error', [
                        'path' => $path,
                        'query' => $query,
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);

                    return ['resultado' => [], 'totalRegistros' => 0, 'error' => 'API retornou erro: ' . $response->status()];
                }

                return $response->json() ?? ['resultado' => [], 'totalRegistros' => 0];
            } catch (\Throwable $e) {
                Log::error('ComprasGov API exception', [
                    'path' => $path,
                    'query' => $query,
                    'error' => $e->getMessage(),
                ]);

                // Don't cache errors — return but don't persist
                Cache::forget($cacheKey);

                return ['resultado' => [], 'totalRegistros' => 0, 'error' => 'Falha na comunicação com a API: ' . $e->getMessage()];
            }
        });
    }

    protected function client(): PendingRequest
    {
        return Http::baseUrl(config('compras.api_base_url'))
            ->acceptJson()
            ->timeout(15)
            ->connectTimeout(10)
            ->retry(2, 300);
    }
}