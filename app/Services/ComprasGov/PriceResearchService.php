<?php

namespace App\Services\ComprasGov;

class PriceResearchService
{
    public function __construct(private readonly ComprasGovApiClient $client)
    {
    }

    public function materialPrices(array $query = []): array
    {
        return $this->client->get('/modulo-pesquisa-preco/1_consultarMaterial', $query);
    }

    public function servicePrices(array $query = []): array
    {
        return $this->client->get('/modulo-pesquisa-preco/3_consultarServico', $query);
    }
}