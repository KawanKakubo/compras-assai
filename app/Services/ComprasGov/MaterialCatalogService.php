<?php

namespace App\Services\ComprasGov;

class MaterialCatalogService
{
    public function __construct(
        private readonly ComprasGovApiClient $client,
        private readonly PdmDictionaryService $pdmDictionary
    ) {
    }

    public function searchItems(array $query = []): array
    {
        $searchTerm = $query['descricaoItem'] ?? '';
        
        // 1. Busca no banco de dados local "ENORME" com Aliases
        $localItems = \App\Models\CatalogItem::where('description', 'ilike', '%' . $searchTerm . '%')
            ->orWhere('pdm_name', 'ilike', '%' . $searchTerm . '%')
            ->orWhere('search_aliases', 'ilike', '%' . $searchTerm . '%')
            ->limit(50)
            ->get();

        if ($localItems->isNotEmpty()) {
            return [
                'resultado' => $localItems->map(fn($item) => [
                    'codigoGrupo' => $item->group_code,
                    'nomeGrupo' => $item->group_name,
                    'codigoClasse' => $item->class_code,
                    'nomeClasse' => $item->class_name,
                    'codigoPdm' => $item->pdm_code,
                    'nomePdm' => $item->pdm_name,
                    'codigoItem' => $item->item_code,
                    'descricaoItem' => $item->description,
                    'statusItem' => $item->is_active,
                    'itemSustentavel' => $item->is_sustainable
                ])->toArray(),
                'totalRegistros' => $localItems->count(),
                'source' => 'local'
            ];
        }

        // 2. Fallback Inteligente: Se não achou local, tenta mapear para um PDM oficial
        // e faz a busca no governo pelo código PDM (que é muito mais estável que texto)
        $mappedPdm = $this->pdmDictionary->findPdmByTerm($searchTerm);
        if ($mappedPdm) {
            $apiQuery = $query;
            unset($apiQuery['descricaoItem']);
            $apiQuery['codigoPdm'] = $mappedPdm;
            
            $apiResult = $this->client->get('/modulo-material/4_consultarItemMaterial', $apiQuery);
            
            if (!empty($apiResult['resultado'])) {
                $apiResult['source'] = 'government_pdm_optimized';
                return $apiResult;
            }
        }

        // 3. Último recurso: Busca textual direta na API do Governo
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