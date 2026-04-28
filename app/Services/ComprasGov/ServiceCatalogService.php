<?php

namespace App\Services\ComprasGov;

class ServiceCatalogService
{
    public function __construct(private readonly ComprasGovApiClient $client)
    {
    }


    public function searchItems(array $query = []): array
    {
        $searchTerm = $query['descricaoItem'] ?? '';
        
        // 1. Busca no banco de dados local "ENORME"
        $localServices = \App\Models\CatalogService::where('description', 'ilike', '%' . $searchTerm . '%')
            ->orWhere('search_aliases', 'ilike', '%' . $searchTerm . '%')
            ->limit(50)
            ->get();

        if ($localServices->isNotEmpty()) {
            return [
                'resultado' => $localServices->map(fn($service) => [
                    'codigoGrupo' => $service->group_code,
                    'nomeGrupo' => $service->group_name,
                    'codigoServico' => $service->service_code,
                    'descricaoServico' => $service->description,
                    'statusServico' => $service->is_active,
                    'servicoSustentavel' => false
                ])->toArray(),
                'totalRegistros' => $localServices->count(),
                'source' => 'local'
            ];
        }

        return $this->client->get('/modulo-servico/6_consultarItemServico', $query);
    }

    public function supplyUnits(array $query = []): array
    {
        return $this->client->get('/modulo-servico/7_consultarUndMedidaServico', $query);
    }
}