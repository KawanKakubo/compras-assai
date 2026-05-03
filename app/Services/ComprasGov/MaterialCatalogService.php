<?php

namespace App\Services\ComprasGov;

class MaterialCatalogService
{
    public function __construct(
        private readonly ComprasGovApiClient $client,
        private readonly PdmDictionaryService $pdmDictionary
    ) {
    }

    public function getGroups(): array
    {
        return $this->client->getTaxonomy('/modulo-material/1_consultarGrupoMaterial');
    }

    public function getClasses(int $groupCode): array
    {
        return $this->client->getTaxonomy('/modulo-material/2_consultarClasseMaterial', [
            'codigoGrupo' => $groupCode
        ]);
    }

    public function getPdms(int $classCode): array
    {
        return $this->client->getTaxonomy('/modulo-material/3_consultarPdmMaterial', [
            'codigoClasse' => $classCode
        ]);
    }

    public function searchItems(array $query = []): array
    {
        $searchTerm = $query['descricaoItem'] ?? '';
        $codigoPdm = $query['codigoPdm'] ?? null;
        $forceGlobal = filter_var($query['force_global'] ?? false, FILTER_VALIDATE_BOOLEAN);
        
        // 1. Se houver código PDM, priorizar busca filtrada (Hierárquica)
        if ($codigoPdm) {
            // Tenta busca local por PDM primeiro
            $localItems = \App\Models\CatalogItem::where('pdm_code', $codigoPdm)
                ->limit(100)
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
                    'source' => 'local_pdm'
                ];
            }

            // Fallback para API do Governo com filtro de PDM
            unset($query['force_global']);
            return $this->client->get('/modulo-material/4_consultarItemMaterial', $query);
        }

        // 2. Se for forçado global (Busca textual na API)
        if ($forceGlobal) {
            unset($query['force_global']);
            return $this->client->get('/modulo-material/4_consultarItemMaterial', $query);
        }

        // 3. Busca textual no banco de dados local
        if ($searchTerm) {
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

            // Fallback Inteligente: Se não achou local, tenta mapear para um PDM oficial
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
        }

        // 4. Último recurso: Busca textual direta na API do Governo
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