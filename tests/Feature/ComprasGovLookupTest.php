<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ComprasGovLookupTest extends TestCase
{
    public function test_material_items_endpoint_proxies_to_compras_gov(): void
    {
        Http::fake([
            config('compras.api_base_url').'/*' => Http::response([
                'resultado' => [
                    [
                        'codigoGrupo' => 71,
                        'nomeGrupo' => 'MOBILIÁRIOS',
                        'codigoClasse' => 7110,
                        'nomeClasse' => 'MOBILIÁRIO PARA ESCRITÓRIO',
                        'codigoPdm' => 313,
                        'nomePdm' => 'CADEIRA ESCRITÓRIO',
                        'codigoItem' => 206504,
                        'descricaoItem' => 'CADEIRA ESCRITÓRIO',
                        'statusItem' => true,
                    ],
                ],
                'totalRegistros' => 1,
                'totalPaginas' => 1,
                'paginasRestantes' => 0,
            ], 200),
        ]);

        $response = $this->getJson('/api/compras-gov/material/items?pagina=2&tamanhoPagina=10&descricaoItem=cadeira&statusItem=1');

        $response->assertOk();
        $response->assertJsonPath('resultado.0.codigoItem', 206504);

        Http::assertSent(function (Request $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return str_contains($request->url(), '/modulo-material/4_consultarItemMaterial')
                && (int) ($query['pagina'] ?? 0) === 2
                && (int) ($query['tamanhoPagina'] ?? 0) === 10
                && ($query['descricaoItem'] ?? null) === 'cadeira'
                && ($query['statusItem'] ?? null) === '1';
        });
    }

    public function test_material_prices_endpoint_requires_catalog_item_code(): void
    {
        $response = $this->getJson('/api/compras-gov/material/prices?tamanhoPagina=10');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['codigoItemCatalogo']);
    }

    public function test_material_prices_success_with_state_data(): void
    {
        Http::fake([
            '*/modulo-pesquisa-preco/1_consultarMaterial*' => Http::response([
                'resultado' => [
                    [
                        'precoUnitario' => 120.50,
                        'dataCompra' => '2026-05-01',
                        'nomeOrgao' => 'Prefeitura de Assaí',
                        'siglaUnidadeMedida' => 'UN'
                    ],
                    [
                        'precoUnitario' => 122.00,
                        'dataCompra' => '2026-05-02',
                        'nomeOrgao' => 'Tribunal de Justiça PR',
                        'siglaUnidadeMedida' => 'UN'
                    ],
                    [
                        'precoUnitario' => 119.90,
                        'dataCompra' => '2026-05-03',
                        'nomeOrgao' => 'Universidade de Londrina',
                        'siglaUnidadeMedida' => 'UN'
                    ]
                ],
                'totalRegistros' => 3
            ], 200)
        ]);

        $response = $this->getJson('/api/compras-gov/material/prices?codigoItemCatalogo=227254');

        $response->assertOk();
        $response->assertJsonPath('fonte', 'Dados Abertos: Preços Praticados PR (CATMAT)');
        $response->assertJsonCount(3, 'resultado');
        $response->assertJsonPath('resultado.0.valorUnitario', 120.50);
        $response->assertJsonPath('resultado.0.unidadeMedida', 'UN');
        $response->assertJsonPath('resultado.0.orgao', 'Prefeitura de Assaí');
    }

    public function test_material_prices_fallback_to_national_on_empty_state_results(): void
    {
        Http::fake([
            '*/modulo-pesquisa-preco/1_consultarMaterial*' => function (Request $request) {
                parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);
                
                // If it is state level, return empty
                if (($query['estado'] ?? '') === 'PR') {
                    return Http::response(['resultado' => []], 200);
                }

                // If it is national (no state), return results
                return Http::response([
                    'resultado' => [
                        [
                            'precoUnitario' => 135.50,
                            'dataCompra' => '2026-04-25',
                            'nomeOrgao' => 'Ministério da Fazenda',
                            'siglaUnidadeMedida' => 'PCT'
                        ]
                    ],
                    'totalRegistros' => 1
                ], 200);
            }
        ]);

        $response = $this->getJson('/api/compras-gov/material/prices?codigoItemCatalogo=227254');

        $response->assertOk();
        $response->assertJsonPath('fonte', 'Dados Abertos: Preços Praticados Nacional (CATMAT)');
        $response->assertJsonCount(1, 'resultado');
        $response->assertJsonPath('resultado.0.valorUnitario', 135.50);
        $response->assertJsonPath('resultado.0.unidadeMedida', 'PCT');
        $response->assertJsonPath('resultado.0.orgao', 'Ministério da Fazenda');
    }

    public function test_service_prices_success_with_state_data(): void
    {
        Http::fake([
            '*/modulo-pesquisa-preco/3_consultarServico*' => Http::response([
                'resultado' => [
                    [
                        'precoUnitario' => 5000.25,
                        'dataCompra' => '2026-05-01',
                        'nomeOrgao' => 'Câmara Municipal de Curitiba',
                        'siglaUnidadeMedida' => 'MÊS'
                    ],
                    [
                        'precoUnitario' => 5100.00,
                        'dataCompra' => '2026-05-02',
                        'nomeOrgao' => 'Prefeitura de Londrina',
                        'siglaUnidadeMedida' => 'MÊS'
                    ],
                    [
                        'precoUnitario' => 4950.00,
                        'dataCompra' => '2026-05-03',
                        'nomeOrgao' => 'Sanepar',
                        'siglaUnidadeMedida' => 'MÊS'
                    ]
                ],
                'totalRegistros' => 3
            ], 200)
        ]);

        // Query with service-specific field codigoServico
        $response = $this->getJson('/api/compras-gov/service/prices?codigoServico=4567');

        $response->assertOk();
        $response->assertJsonPath('fonte', 'Dados Abertos: Preços Praticados PR (CATSER)');
        $response->assertJsonCount(3, 'resultado');
        $response->assertJsonPath('resultado.0.valorUnitarioHomologado', 5000.25);
        $response->assertJsonPath('resultado.0.unidadeMedida', 'MÊS');
    }

    public function test_service_prices_fallback_to_national_on_empty_state_results(): void
    {
        Http::fake([
            '*/modulo-pesquisa-preco/3_consultarServico*' => function (Request $request) {
                parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);

                if (($query['estado'] ?? '') === 'PR') {
                    return Http::response(['resultado' => []], 200);
                }

                return Http::response([
                    'resultado' => [
                        [
                            'precoUnitario' => 5500.75,
                            'dataCompra' => '2026-04-20',
                            'nomeOrgao' => 'Receita Federal SP',
                            'siglaUnidadeMedida' => 'UN'
                        ]
                    ],
                    'totalRegistros' => 1
                ], 200);
            }
        ]);

        $response = $this->getJson('/api/compras-gov/service/prices?codigoServico=4567');

        $response->assertOk();
        $response->assertJsonPath('fonte', 'Dados Abertos: Preços Praticados Nacional (CATSER)');
        $response->assertJsonCount(1, 'resultado');
        $response->assertJsonPath('resultado.0.valorUnitarioHomologado', 5500.75);
        $response->assertJsonPath('resultado.0.unidadeMedida', 'UN');
    }
}
