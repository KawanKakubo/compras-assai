<?php

namespace App\Services\ComprasGov;

use App\Models\ComprasGov\GovCatalogTaxonomy;
use Illuminate\Support\Facades\Log;

class ServiceCatalogService
{
    public function __construct(private readonly ComprasGovApiClient $client)
    {
    }

    public function getSections(): array
    {
        return $this->getOrSyncTaxonomy('service', 'section', null, '/modulo-servico/1_consultarSecaoServico', 'Secao');
    }

    public function getDivisions(string $sectionCode): array
    {
        return $this->getOrSyncTaxonomy('service', 'division', $sectionCode, '/modulo-servico/2_consultarDivisaoServico', 'Divisao', ['codigoSecao' => $sectionCode]);
    }

    public function getGroups(int $divisionCode): array
    {
        return $this->getOrSyncTaxonomy('service', 'group', (string)$divisionCode, '/modulo-servico/3_consultarGrupoServico', 'Grupo', ['codigoDivisao' => $divisionCode]);
    }

    public function getClasses(int $groupCode): array
    {
        return $this->getOrSyncTaxonomy('service', 'class', (string)$groupCode, '/modulo-servico/4_consultarClasseServico', 'Classe', ['codigoGrupo' => $groupCode]);
    }

    public function getSubclasses(int $classCode): array
    {
        return $this->getOrSyncTaxonomy('service', 'subclass', (string)$classCode, '/modulo-servico/5_consultarSubClasseServico', 'Subclasse', ['codigoClasse' => $classCode]);
    }

    /**
     * Tenta buscar no banco local, se não houver, busca na API e salva.
     */
    private function getOrSyncTaxonomy(string $catalogType, string $level, ?string $parentCode, string $apiPath, string $normSuffix, array $query = []): array
    {
        // 1. Tenta local
        $local = GovCatalogTaxonomy::level($catalogType, $level, $parentCode)->get();

        if ($local->isNotEmpty()) {
            return [
                'resultado' => $local->map(fn($item) => [
                    "codigo$normSuffix" => $item->code,
                    "nome$normSuffix" => $item->description,
                    'codigo' => $item->code,
                    'descricao' => $item->description
                ])->toArray(),
                'totalRegistros' => $local->count(),
                'source' => 'local_db'
            ];
        }

        // 2. Busca na API
        $data = $this->client->getTaxonomy($apiPath, $query);

        // Fallback inteligente: Se o Governo falhar com erro de JPA no filtro, 
        // tentamos buscar TUDO e filtrar localmente em PHP.
        if (!empty($data['error']) && str_contains($data['error'], 'EntityManager') && !empty($query)) {
            Log::warning("Fallback Taxonomy: Erro JPA no Governo para {$apiPath}. Tentando buscar sem filtros e filtrar localmente.");
            
            // Tenta buscar sem o filtro principal (ex: codigoClasse)
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
            // Normaliza para salvar no banco
            $items = $this->normalizeTaxonomy($data, $normSuffix);
            
            foreach ($items['resultado'] as $item) {
                try {
                    GovCatalogTaxonomy::updateOrCreate([
                        'catalog_type' => $catalogType,
                        'level_name' => $level,
                        'parent_code' => $parentCode,
                        'code' => (string)($item["codigo$normSuffix"] ?? $item['codigo']),
                    ], [
                        'description' => $item["nome$normSuffix"] ?? $item['descricao'],
                    ]);
                } catch (\Exception $e) {
                    Log::error("Erro ao salvar taxonomia local: " . $e->getMessage());
                }
            }
            
            $data['source'] = 'government_api_synced';
            return $items;
        }

        return $data;
    }

    private function normalizeTaxonomy(array $data, string $suffix): array
    {
        if (empty($data['resultado'])) {
            return $data;
        }

        $data['resultado'] = array_map(function ($item) use ($suffix) {
            $code = $item['codigo'] ?? $item["codigo$suffix"] ?? null;
            $name = $item['descricao'] ?? $item['nome'] ?? $item["nome$suffix"] ?? $item["descricao$suffix"] ?? null;
            
            return [
                "codigo$suffix" => $code,
                "nome$suffix" => $name,
                'codigo' => $code,
                'descricao' => $name
            ];
        }, $data['resultado']);

        return $data;
    }

    public function searchItems(array $query = []): array
    {
        $searchTerm = $query['descricaoItem'] ?? $query['descricaoServico'] ?? '';
        $codigoSubclasse = $query['codigoSubclasse'] ?? null;
        $forceGlobal = filter_var($query['force_global'] ?? false, FILTER_VALIDATE_BOOLEAN);

        // 1. Tenta local primeiro
        if (!$forceGlobal) {
            $localServices = collect();
            
            if ($codigoSubclasse || $searchTerm) {
                // Tenta buscar com subclasse (5 dígitos)
                $queryBuilder = \App\Models\CatalogService::query();
                if ($codigoSubclasse) {
                    $queryBuilder->where('service_code', 'like', $codigoSubclasse . '%');
                }
                if ($searchTerm) {
                    $queryBuilder->where(function($q) use ($searchTerm) {
                        $upperSearch = mb_strtoupper($searchTerm);
                        $q->whereRaw('UPPER(description) LIKE ?', ['%' . $upperSearch . '%'])
                          ->orWhereRaw('UPPER(search_aliases) LIKE ?', ['%' . $upperSearch . '%']);
                    });
                }
                $localServices = $queryBuilder->limit(200)->get();
                
                // Se não achou e temos codigoSubclasse, tenta pela classe (4 dígitos)
                if ($localServices->isEmpty() && $codigoSubclasse) {
                    $classCode = substr((string)$codigoSubclasse, 0, 4);
                    $queryBuilder = \App\Models\CatalogService::query()->where('service_code', 'like', $classCode . '%');
                    if ($searchTerm) {
                        $queryBuilder->where(function($q) use ($searchTerm) {
                            $upperSearch = mb_strtoupper($searchTerm);
                            $q->whereRaw('UPPER(description) LIKE ?', ['%' . $upperSearch . '%']);
                        });
                    }
                    $localServices = $queryBuilder->limit(200)->get();
                }
                
                // Se ainda não achou e temos codigoSubclasse, tenta pelo grupo (3 dígitos)
                if ($localServices->isEmpty() && $codigoSubclasse) {
                    $groupCode = substr((string)$codigoSubclasse, 0, 3);
                    $queryBuilder = \App\Models\CatalogService::query()->where('service_code', 'like', $groupCode . '%');
                    if ($searchTerm) {
                        $queryBuilder->where(function($q) use ($searchTerm) {
                            $upperSearch = mb_strtoupper($searchTerm);
                            $q->whereRaw('UPPER(description) LIKE ?', ['%' . $upperSearch . '%']);
                        });
                    }
                    $localServices = $queryBuilder->limit(200)->get();
                }
            }

            if ($localServices->isNotEmpty()) {
                return [
                    'resultado' => $localServices->map(fn($service) => [
                        'codigoGrupo' => $service->group_code,
                        'nomeGrupo' => $service->group_name,
                        'codigoServico' => (int)$service->service_code,
                        'descricaoServico' => $service->description,
                        'statusServico' => (bool)$service->is_active,
                        'servicoSustentavel' => false
                    ])->toArray(),
                    'totalRegistros' => $localServices->count(),
                    'source' => 'local'
                ];
            }
        }

        // 2. Busca na API
        unset($query['force_global']);
        $data = $this->client->get('/modulo-servico/6_consultarItemServico', $query);

        // Fallback progressivo de hierarquia para serviços se o resultado for vazio e tiver codigoSubclasse
        if (empty($data['resultado']) && empty($data['error']) && $codigoSubclasse) {
            // Se a busca por subclasse (5 dígitos) deu vazia, tenta por classe (4 dígitos)
            $classCode = substr((string)$codigoSubclasse, 0, 4);
            Log::info("Fallback Service Items: Nenhum resultado para subclasse {$codigoSubclasse}. Tentando por classe {$classCode}");
            
            $fallbackQuery = $query;
            unset($fallbackQuery['codigoSubclasse']);
            $fallbackQuery['codigoClasse'] = (int)$classCode;
            
            $data = $this->client->get('/modulo-servico/6_consultarItemServico', $fallbackQuery);
            
            // Se ainda assim for vazio, tenta por grupo (3 dígitos)
            if (empty($data['resultado']) && empty($data['error'])) {
                $groupCode = substr((string)$codigoSubclasse, 0, 3);
                Log::info("Fallback Service Items: Nenhum resultado para classe {$classCode}. Tentando por grupo {$groupCode}");
                
                unset($fallbackQuery['codigoClasse']);
                $fallbackQuery['codigoGrupo'] = (int)$groupCode;
                
                $data = $this->client->get('/modulo-servico/6_consultarItemServico', $fallbackQuery);
            }
        }

        // Fallback inteligente para itens de serviço (Erro JPA no filtro de Subclasse)
        if (!empty($data['error']) && str_contains($data['error'], 'EntityManager') && isset($query['codigoSubclasse'])) {
            Log::warning("Fallback Items: Erro JPA no Governo para Itens de Serviço. Tentando busca alternativa.");
            
            // Tenta buscar sem o filtro de subclasse, talvez usando apenas status e uma página maior
            $unfilteredItems = $this->client->get('/modulo-servico/6_consultarItemServico', [
                'statusServico' => 'true',
                'tamanhoPagina' => 500
            ]);

            if (!empty($unfilteredItems['resultado'])) {
                $subCode = $query['codigoSubclasse'];
                $filtered = array_values(array_filter($unfilteredItems['resultado'], function($item) use ($subCode) {
                    return ($item['codigoSubclasse'] ?? null) == $subCode;
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

        // 3. Popula o sistema de forma inteligente (Eager Sync)
        if (!empty($data['resultado']) && empty($data['error'])) {
            foreach ($data['resultado'] as $item) {
                try {
                    \App\Models\CatalogService::updateOrCreate([
                        'service_code' => (string)$item['codigoServico'],
                    ], [
                        'description' => $item['descricaoServico'] ?? $item['nomeServico'],
                        'group_code' => $item['codigoGrupo'] ?? null,
                        'group_name' => $item['nomeGrupo'] ?? null,
                        'is_active' => $item['statusServico'] ?? true,
                    ]);
                } catch (\Exception $e) {
                    Log::error("Erro ao persistir item de serviço local: " . $e->getMessage());
                }
            }
            $data['source'] = 'government_api_eager_synced';
        }

        return $data;
    }

    public function supplyUnits(array $query = []): array
    {
        return $this->client->get('/modulo-servico/7_consultarUndMedidaServico', $query);
    }
}