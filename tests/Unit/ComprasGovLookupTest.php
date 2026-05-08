<?php

namespace Tests\Unit;

use App\Services\ComprasGov\MaterialCatalogService;
use App\Services\ComprasGov\ServiceCatalogService;
use App\Services\ComprasGov\ComprasGovApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class ComprasGovLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_material_catalog_service_returns_fallback_for_papel()
    {
        $mockClient = Mockery::mock(ComprasGovApiClient::class);
        $mockClient->shouldReceive('get')
            ->once()
            ->with('/modulo-material/4_consultarItemMaterial', ['descricaoItem' => 'papel'])
            ->andReturn([
                'resultado' => [
                    [
                        'codigoGrupo' => 75,
                        'nomeGrupo' => 'MATERIAL DE ESCRITÓRIO',
                        'codigoClasse' => 7530,
                        'nomeClasse' => 'ARTIGOS DE PAPELARIA',
                        'codigoPdm' => 17351,
                        'nomePdm' => 'PAPEL SULFITE',
                        'codigoItem' => 150032,
                        'descricaoItem' => 'PAPEL SULFITE, TIPO: ALCALINO, COR: BRANCO, FORMATO: A4, GRAMATURA: 75 G/M2',
                        'statusItem' => true,
                        'itemSustentavel' => true
                    ]
                ],
                'totalRegistros' => 1
            ]);

        $this->app->instance(ComprasGovApiClient::class, $mockClient);

        $service = $this->app->make(MaterialCatalogService::class);
        $results = $service->searchItems(['descricaoItem' => 'papel']);

        $this->assertArrayHasKey('resultado', $results);
        $this->assertGreaterThan(0, $results['totalRegistros']);
        $this->assertEquals('PAPEL SULFITE', $results['resultado'][0]['nomePdm']);
    }

    public function test_service_catalog_service_returns_fallback_for_limpeza()
    {
        $mockClient = Mockery::mock(ComprasGovApiClient::class);
        $mockClient->shouldReceive('get')
            ->once()
            ->with('/modulo-servico/6_consultarItemServico', ['descricaoItem' => 'limpeza'])
            ->andReturn([
                'resultado' => [
                    [
                        'codigoGrupo' => 2,
                        'nomeGrupo' => 'SERVIÇOS DE LIMPEZA E CONSERVAÇÃO',
                        'codigoServico' => 2020,
                        'descricaoServico' => 'SERVIÇO DE LIMPEZA E CONSERVAÇÃO PREDIAL, INCLUINDO MATERIAIS E EQUIPAMENTOS',
                        'statusServico' => true,
                        'servicoSustentavel' => false
                    ]
                ],
                'totalRegistros' => 1
            ]);

        $this->app->instance(ComprasGovApiClient::class, $mockClient);

        $service = $this->app->make(ServiceCatalogService::class);
        $results = $service->searchItems(['descricaoItem' => 'limpeza']);

        $this->assertArrayHasKey('resultado', $results);
        $this->assertGreaterThan(0, $results['totalRegistros']);
        $this->assertEquals('SERVIÇOS DE LIMPEZA E CONSERVAÇÃO', $results['resultado'][0]['nomeGrupo']);
    }

    public function test_it_forwards_to_api_if_not_in_fallback()
    {
        $mockClient = Mockery::mock(ComprasGovApiClient::class);
        // Esperamos que ele chame o cliente da API real quando não encontrar no fallback
        $mockClient->shouldReceive('get')
            ->once()
            ->with('/modulo-material/4_consultarItemMaterial', ['descricaoItem' => 'item_obscuro_nao_cadastrado'])
            ->andReturn(['resultado' => [], 'totalRegistros' => 0]);

        $this->app->instance(ComprasGovApiClient::class, $mockClient);

        $service = $this->app->make(MaterialCatalogService::class);
        $results = $service->searchItems(['descricaoItem' => 'item_obscuro_nao_cadastrado']);
        
        $this->assertEmpty($results['resultado']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
