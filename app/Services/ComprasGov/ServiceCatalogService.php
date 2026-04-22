<?php

namespace App\Services\ComprasGov;

class ServiceCatalogService
{
    public function __construct(private readonly ComprasGovApiClient $client)
    {
    }

    private array $localFallbackItems = [
        [
            "codigoGrupo" => 1, "nomeGrupo" => "SERVIÇOS DE MANUTENÇÃO",
            "codigoClasse" => 1010, "nomeClasse" => "MANUTENÇÃO DE COMPUTADORES E EQUIPAMENTOS DE INFORMÁTICA",
            "codigoServico" => 15001,
            "descricaoServico" => "SERVIÇO DE MANUTENÇÃO PREVENTIVA E CORRETIVA EM MICROCOMPUTADORES E NOTEBOOKS",
            "statusServico" => true, "servicoSustentavel" => false
        ],
        [
            "codigoGrupo" => 2, "nomeGrupo" => "SERVIÇOS DE LIMPEZA E CONSERVAÇÃO",
            "codigoClasse" => 2010, "nomeClasse" => "LIMPEZA PREDIAL",
            "codigoServico" => 25002,
            "descricaoServico" => "SERVIÇO CONTINUADO DE LIMPEZA, CONSERVAÇÃO E HIGIENIZAÇÃO DE AMBIENTES INTERNOS E EXTERNOS",
            "statusServico" => true, "servicoSustentavel" => true
        ],
        [
            "codigoGrupo" => 3, "nomeGrupo" => "SERVIÇOS DE ENGENHARIA",
            "codigoClasse" => 3010, "nomeClasse" => "OBRAS CIVIS",
            "codigoServico" => 35003,
            "descricaoServico" => "SERVIÇO DE ENGENHARIA PARA REFORMA DE PRÉDIO PÚBLICO COM FORNECIMENTO DE MATERIAL E MÃO DE OBRA",
            "statusServico" => true, "servicoSustentavel" => false
        ]
    ];

    public function searchItems(array $query = []): array
    {
        if (!empty($query['descricaoItem'])) {
            $searchTerm = mb_strtolower($query['descricaoItem'], 'UTF-8');
            $results = array_filter($this->localFallbackItems, function ($item) use ($searchTerm) {
                $desc = mb_strtolower($item['descricaoServico'], 'UTF-8');
                $nomeClasse = mb_strtolower($item['nomeClasse'], 'UTF-8');
                return str_contains($desc, $searchTerm) || str_contains($nomeClasse, $searchTerm);
            });

            if (!empty($results)) {
                return [
                    'resultado' => array_values($results),
                    'totalRegistros' => count($results),
                    'totalPaginas' => 1,
                    'paginasRestantes' => 0
                ];
            }
        }

        return $this->client->get('/modulo-servico/6_consultarItemServico', $query);
    }

    public function supplyUnits(array $query = []): array
    {
        return $this->client->get('/modulo-servico/7_consultarUndMedidaServico', $query);
    }
}