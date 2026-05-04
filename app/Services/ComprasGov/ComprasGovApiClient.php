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
     * Results are cached for 1 hour by default.
     */
    public function get(string $path, array $query = []): array
    {
        $cacheKey = 'compras_gov:' . md5($path . serialize($query));

        $data = Cache::get($cacheKey);
        if ($data && empty($data['error'])) {
            return $data;
        }

        $result = $this->fetchFromApi($path, $query, $cacheKey);
        
        if (empty($result['error'])) {
            Cache::put($cacheKey, $result, now()->addHour());
        }

        return $result;
    }

    /**
     * Specialized GET for static taxonomies (Groups, Classes, etc.)
     * Caches for 24 hours and provides a backup fallback if the government API is down.
     */
    public function getTaxonomy(string $path, array $query = []): array
    {
        $cacheKey = 'compras_gov_tax:' . md5($path . serialize($query));
        $backupKey = 'compras_gov_tax_backup:' . md5($path . serialize($query));

        // 1. Tenta pegar do cache principal (24h)
        $data = Cache::get($cacheKey);
        if ($data) {
            return $data;
        }

        // 2. Se não tem no cache, tenta buscar na API com timeout menor e sem retry excessivo
        // Taxonomias devem ser rápidas; se demorar > 60s, algo está errado no Gov.
        $apiResult = $this->fetchFromApi($path, $query, $cacheKey, false, 60, 2);

        // 3. Se a API falhou (tem erro), tenta o backup
        if (!empty($apiResult['error'])) {
            $backupData = Cache::get($backupKey);
            if ($backupData) {
                $backupData['_stale_warning'] = true;
                return $backupData;
            }
        } else {
            // 4. Se a API funcionou, salva no cache principal e no backup (longo prazo)
            Cache::put($cacheKey, $apiResult, now()->addDay());
            Cache::put($backupKey, $apiResult, now()->addDays(30));
        }

        return $apiResult;
    }

    /**
     * Internal method to perform the HTTP call with error handling.
     */
    protected function fetchFromApi(string $path, array $query = [], ?string $cacheKey = null, bool $forgetOnFailure = true, ?int $timeout = null, ?int $retries = null): array
    {
        try {
            // Default timeouts: searches are heavy (60s), others are moderate (30s)
            if ($timeout === null) {
                $timeout = str_contains($path, 'consultarItem') ? 120 : 60;
            }
            
            $request = $this->client($timeout);
            if ($retries !== null) {
                $request->retry($retries, 200);
            }

            $response = $request->get($path, $query);

            if ($response->failed()) {
                Log::warning('ComprasGov API error', [
                    'path' => $path,
                    'query' => $query,
                    'status' => $response->status(),
                ]);

                $errorMessage = 'A API do Governo retornou um erro inesperado (Status ' . $response->status() . ').';
                
                $body = $response->body();
                if (strlen($body) > 5 && strlen($body) < 200 && !str_contains($body, '<html')) {
                    $errorMessage = 'Erro do Governo: ' . trim(strip_tags($body));
                }

                if (in_array($response->status(), [500, 502, 503, 504]) || str_contains($body, 'EntityManager')) {
                    $errorMessage = 'O sistema do Governo está temporariamente instável ou sobrecarregado (Erro JPA/EntityManager). Tentaremos novamente em instantes.';
                    
                    // Se for erro de EntityManager, vale a pena forçar uma pequena espera e tentar de novo se estivermos em um loop de sync
                    if (str_contains($body, 'EntityManager') && $retries > 0) {
                        sleep(1);
                    }
                }

                return [
                    'resultado' => [],
                    'totalRegistros' => 0,
                    'error' => $errorMessage
                ];
            }

            $data = $response->json();
            if (is_null($data)) {
                 return ['resultado' => [], 'totalRegistros' => 0, 'error' => 'A API do Governo retornou um formato inválido.'];
            }

            return $data;
        } catch (\Throwable $e) {
            Log::error('ComprasGov API exception', [
                'path' => $path,
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            if ($forgetOnFailure && $cacheKey) {
                Cache::forget($cacheKey);
                // Also forget the taxonomy key just in case
                Cache::forget(str_replace('compras_gov:', 'compras_gov_tax:', $cacheKey));
            }

            $msg = $e->getMessage();
            if (str_contains($msg, 'timed out') || str_contains($msg, 'cURL error 28')) {
                $msg = 'O servidor do Governo demorou muito para responder e a conexão foi encerrada.';
            }

            return [
                'resultado' => [],
                'totalRegistros' => 0,
                'error' => 'Falha de conexão com o Governo: ' . $msg
            ];
        }
    }

    protected function client(int $timeout = 15): PendingRequest
    {
        return Http::baseUrl(config('compras.api_base_url'))
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept' => 'application/json',
            ])
            ->timeout($timeout)
            ->connectTimeout(5)
            ->retry(2, 300);
    }
}