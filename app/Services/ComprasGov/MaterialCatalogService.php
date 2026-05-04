<?php

namespace App\Services\ComprasGov;

use App\Models\ComprasGov\GovCatalogTaxonomy;
use Illuminate\Support\Facades\Log;

class MaterialCatalogService
{
    public function __construct(
        private readonly ComprasGovApiClient $client,
        private readonly PdmDictionaryService $pdmDictionary
    ) {
    }

    public function getGroups(): array
    {
        return $this->getOrSyncTaxonomy('material', 'group', null, '/modulo-material/1_consultarGrupoMaterial', 'Grupo');
    }

    public function getClasses(int $groupCode): array
    {
        return $this->getOrSyncTaxonomy('material', 'class', (string)$groupCode, '/modulo-material/2_consultarClasseMaterial', 'Classe', ['codigoGrupo' => $groupCode]);
    }

    public function getPdms(int $classCode): array
    {
        return $this->getOrSyncTaxonomy('material', 'pdm', (string)$classCode, '/modulo-material/3_consultarPdmMaterial', 'Pdm', ['codigoClasse' => $classCode]);
    }

    /**
     * Tenta buscar no banco local, se não houver, busca na API e salva.
     */
    private function getOrSyncTaxonomy(string $catalogType, string $level, ?string $parentCode, string $apiPath, string $normSuffix, array $query = []): array
    {
        $local = GovCatalogTaxonomy::level($catalogType, $level, $parentCode)->get();

        if ($local->isNotEmpty()) {
            return [
                'resultado' => $local->map(fn($item) => [
                    "codigo$normSuffix" => (int)$item->code,
                    "nome$normSuffix" => $item->description,
                    'codigo' => (int)$item->code,
                    'descricao' => $item->description
                ])->toArray(),
                'totalRegistros' => $local->count(),
                'source' => 'local_db'
            ];
        }

        $data = $this->client->getTaxonomy($apiPath, $query);

        // Fallback inteligente: Se o Governo falhar com erro de JPA no filtro, 
        // tentamos buscar TUDO e filtrar localmente em PHP.
        if (!empty($data['error']) && str_contains($data['error'], 'EntityManager') && !empty($query)) {
            Log::warning("Fallback Material Taxonomy: Erro JPA no Governo para {$apiPath}. Tentando buscar sem filtros e filtrar localmente.");
            
            $unfilteredData = $this->client->getTaxonomy($apiPath, ['tamanhoPagina' => 500]);
            
            if (!empty($unfilteredData['resultado'])) {
                $filterKey = array_key_first($query);
                $filterValue = $query[$filterKey];

                $filtered = array_values(array_filter($unfilteredData['resultado'], function($item) use ($filterKey, $filterValue) {
                    return ($item[$filterKey] ?? null) == $filterValue;
                }));

                if (!empty($filtered)) {
                    $data = [
                        'resultado' => $filtered,
                        'totalRegistros' => count($filtered),
                        'source' => 'government_api_local_filtered'
                    ];
                }
            }
        }

        if (!empty($data['resultado']) && empty($data['error'])) {
            foreach ($data['resultado'] as $item) {
                try {
                    $code = (string)($item['codigo'] ?? $item["codigo$normSuffix"] ?? null);
                    $desc = $item['descricao'] ?? $item['nome'] ?? $item["nome$normSuffix"] ?? $item["descricao$normSuffix"] ?? null;

                    if ($code && $desc) {
                        GovCatalogTaxonomy::updateOrCreate([
                            'catalog_type' => $catalogType,
                            'level_name' => $level,
                            'parent_code' => $parentCode,
                            'code' => $code,
                        ], [
                            'description' => $desc,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error("Erro ao salvar taxonomia local (material): " . $e->getMessage());
                }
            }
            $data['source'] = 'government_api_synced';
        }

        return $data;
    }

    public function searchItems(array $query = []): array
    {
        $searchTerm = $query['descricaoItem'] ?? '';
        $codigoPdm = $query['codigoPdm'] ?? null;
        $forceGlobal = filter_var($query['force_global'] ?? false, FILTER_VALIDATE_BOOLEAN);
        
        // 1. Tenta local primeiro
        if ($codigoPdm && !$forceGlobal) {
            $localItems = \App\Models\CatalogItem::where('pdm_code', $codigoPdm)
                ->limit(200)
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
                        'codigoItem' => (int)$item->item_code,
                        'descricaoItem' => $item->description,
                        'statusItem' => (bool)$item->is_active,
                        'itemSustentavel' => (bool)$item->is_sustainable
                    ])->toArray(),
                    'totalRegistros' => $localItems->count(),
                    'source' => 'local_db'
                ];
            }
        }

        // 2. Busca na API
        unset($query['force_global']);
        $data = $this->client->get('/modulo-material/4_consultarItemMaterial', $query);

        // Fallback inteligente para itens de material (Erro JPA no filtro de PDM)
        if (!empty($data['error']) && str_contains($data['error'], 'EntityManager') && isset($query['codigoPdm'])) {
            Log::warning("Fallback Material Items: Erro JPA no Governo para Itens de Material. Tentando busca alternativa.");
            
            $unfilteredItems = $this->client->get('/modulo-material/4_consultarItemMaterial', [
                'statusItem' => 'true',
                'tamanhoPagina' => 500
            ]);

            if (!empty($unfilteredItems['resultado'])) {
                $pdmCode = $query['codigoPdm'];
                $filtered = array_values(array_filter($unfilteredItems['resultado'], function($item) use ($pdmCode) {
                    return ($item['codigoPdm'] ?? null) == $pdmCode;
                }));

                if (!empty($filtered)) {
                    $data = [
                        'resultado' => $filtered,
                        'totalRegistros' => count($filtered),
                        'source' => 'government_api_items_local_filtered'
                    ];
                }
            }
        }

        // 3. Popula o sistema (Eager Sync)
        if (!empty($data['resultado']) && empty($data['error'])) {
            foreach ($data['resultado'] as $item) {
                try {
                    \App\Models\CatalogItem::updateOrCreate([
                        'item_code' => (string)$item['codigoItem'],
                    ], [
                        'description' => $item['descricaoItem'],
                        'pdm_code' => $item['codigoPdm'] ?? $codigoPdm,
                        'pdm_name' => $item['nomePdm'] ?? null,
                        'class_code' => $item['codigoClasse'] ?? null,
                        'class_name' => $item['nomeClasse'] ?? null,
                        'group_code' => $item['codigoGrupo'] ?? null,
                        'group_name' => $item['nomeGrupo'] ?? null,
                        'is_sustainable' => $item['itemSustentavel'] ?? false,
                        'is_active' => $item['statusItem'] ?? true,
                    ]);
                } catch (\Exception $e) {
                    Log::error("Erro ao persistir item de material local: " . $e->getMessage());
                }
            }
            $data['source'] = 'government_api_eager_synced';
        }

        return $data;
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