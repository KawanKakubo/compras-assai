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
}
