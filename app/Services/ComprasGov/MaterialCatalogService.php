<?php

namespace App\Services\ComprasGov;

class MaterialCatalogService
{
    public function __construct(private readonly ComprasGovApiClient $client)
    {
    }

    public function searchItems(array $query = []): array
    {
        return $this->client->get('/modulo-material/4_consultarItemMaterial', $query);
    }

    public function supplyUnits(array $query = []): array
    {
        return $this->client->get('/modulo-material/6_consultarMaterialUnidadeFornecimento', $query);
    }

    public function characteristics(array $query = []): array
    {
        return $this->client->get('/modulo-material/7_consultarMaterialCaracteristicas', $query);
    }
}