<?php

namespace Tests\Unit;

use App\Services\ComprasGov\MaterialCatalogService;
use App\Services\ComprasGov\ServiceCatalogService;
use App\Services\ComprasGov\ComprasGovApiClient;
use PHPUnit\Framework\TestCase;
use Mockery;

class ComprasGovLookupTest extends TestCase
{
    public function test_material_catalog_service_returns_fallback_for_papel()
    {
        $mockClient = Mockery::mock(ComprasGovApiClient::class);
        $service = new MaterialCatalogService($mockClient);

        $results = $service->searchItems(['descricaoItem' => 'papel']);

        $this->assertArrayHasKey('resultado', $results);
        $this->assertGreaterThan(0, $results['totalRegistros']);
        $this->assertEquals('PAPEL SULFITE', $results['resultado'][0]['nomePdm']);
    }

    public function test_service_catalog_service_returns_fallback_for_limpeza()
    {
        $mockClient = Mockery::mock(ComprasGovApiClient::class);
        $service = new ServiceCatalogService($mockClient);

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

        $service = new MaterialCatalogService($mockClient);
        $results = $service->searchItems(['descricaoItem' => 'item_obscuro_nao_cadastrado']);
        
        $this->assertEmpty($results['resultado']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
