<?php

namespace App\Services\ComprasGov;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class ComprasGovApiClient
{
    public function get(string $path, array $query = []): array
    {
        return $this->client()
            ->get($path, $query)
            ->throw()
            ->json();
    }

    protected function client(): PendingRequest
    {
        return Http::baseUrl(config('compras.api_base_url'))
            ->acceptJson()
            ->retry(2, 200);
    }
}