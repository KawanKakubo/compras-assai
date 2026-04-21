<?php

namespace App\Services\ComprasGov;

class ServiceCatalogService
{
    public function __construct(private readonly ComprasGovApiClient $client)
    {
    }

    public function searchItems(array $query = []): array
    {
        return $this->client->get('/modulo-servico/6_consultarItemServico', $query);
    }

    public function supplyUnits(array $query = []): array
    {
        return $this->client->get('/modulo-servico/7_consultarUndMedidaServico', $query);
    }
}