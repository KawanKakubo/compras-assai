<?php

namespace App\Services\ComprasGov;

class ServiceCatalogService
{
    public function __construct(private readonly ComprasGovApiClient $client)
    {
    }


    public function getSections(): array
    {
        return $this->client->getTaxonomy('/modulo-servico/1_consultarSecaoServico');
    }

    public function getDivisions(string $sectionCode): array
    {
        return $this->client->getTaxonomy('/modulo-servico/2_consultarDivisaoServico', [
            'codigoSecao' => $sectionCode
        ]);
    }

    public function getGroups(int $divisionCode): array
    {
        return $this->client->getTaxonomy('/modulo-servico/3_consultarGrupoServico', [
            'codigoDivisao' => $divisionCode
        ]);
    }

    public function getClasses(int $groupCode): array
    {
        return $this->client->getTaxonomy('/modulo-servico/4_consultarClasseServico', [
            'codigoGrupo' => $groupCode
        ]);
    }

    public function getSubclasses(int $classCode): array
    {
        return $this->client->getTaxonomy('/modulo-servico/5_consultarSubClasseServico', [
            'codigoClasse' => $classCode
        ]);
    }

    public function searchItems(array $query = []): array
    {
        $searchTerm = $query['descricaoItem'] ?? $query['descricaoServico'] ?? '';
        $codigoSubclasse = $query['codigoSubclasse'] ?? null;
        $forceGlobal = filter_var($query['force_global'] ?? false, FILTER_VALIDATE_BOOLEAN);

        // 1. Se houver código Subclasse, priorizar busca filtrada (Hierárquica)
        if ($codigoSubclasse) {
            // Tenta busca local por subclasse primeiro
            $localServices = \App\Models\CatalogService::where('subclass_code', $codigoSubclasse)
                ->limit(100)
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
                    'source' => 'local_subclass'
                ];
            }

            // Fallback para API do Governo com filtro de Subclasse
            unset($query['force_global']);
            return $this->client->get('/modulo-servico/6_consultarItemServico', $query);
        }

        // 2. Se for forçado global, vai direto para a API
        if ($forceGlobal) {
            unset($query['force_global']);
            return $this->client->get('/modulo-servico/6_consultarItemServico', $query);
        }

        // 3. Busca no banco de dados local por termo
        if ($searchTerm) {
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

            // Fallback Inteligente: O CATSER Oficial não busca por Texto.
            try {
                $pncpUrl = "https://pncp.gov.br/api/search/?q=" . urlencode($searchTerm) . "&tipos_documento=edital&ordem=relevancia";
                $response = \Illuminate\Support\Facades\Http::timeout(5)->get($pncpUrl);
                
                if ($response->successful() && !empty($response->json()['items'])) {
                    $foundCodes = [];
                    $results = [];
                    $editais = array_slice($response->json()['items'], 0, 5);
                    
                    foreach ($editais as $edital) {
                        $cnpj = $edital['orgao_cnpj'];
                        $ano = $edital['ano'];
                        $seq = $edital['numero_sequencial'];
                        
                        $itemsResp = \Illuminate\Support\Facades\Http::timeout(3)->get("https://pncp.gov.br/api/pncp/v1/orgaos/{$cnpj}/compras/{$ano}/{$seq}/itens");
                        if ($itemsResp->successful()) {
                            foreach ($itemsResp->json() as $item) {
                                $code = $item['codigoItem'] ?? null;
                                if ($code && !in_array($code, $foundCodes)) {
                                    $foundCodes[] = $code;
                                    $results[] = [
                                        'codigoServico' => (int) $code,
                                        'descricaoServico' => $item['descricao'] ?? $searchTerm,
                                        'nomeServico' => $item['descricao'] ?? $searchTerm,
                                        'statusServico' => true,
                                        'servicoSustentavel' => false
                                    ];
                                }
                            }
                        }
                        if (count($results) >= 10) break;
                    }
                    
                    if (!empty($results)) {
                        return [
                            'resultado' => $results,
                            'totalRegistros' => count($results),
                            'source' => 'government_pncp_discovery'
                        ];
                    }
                }
            } catch (\Throwable $e) {
            }
        }

        // 4. Último recurso: CATSER Puro
        return $this->client->get('/modulo-servico/6_consultarItemServico', $query);
    }

    public function supplyUnits(array $query = []): array
    {
        return $this->client->get('/modulo-servico/7_consultarUndMedidaServico', $query);
    }
}