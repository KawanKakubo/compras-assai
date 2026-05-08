<?php

namespace Tests\Feature;

use App\Models\CatalogItem;
use App\Models\CatalogService;
use App\Models\ComprasGov\GovCatalogTaxonomy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncCatalogEtlCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_etl_command_runs_completely_and_syncs_catalog_hierarchically()
    {
        // 1. Mock Compras.gov API responses for Materials & Services
        Http::fake([
            '*/modulo-material/4_consultarItemMaterial*' => Http::response([
                'resultado' => [
                    [
                        'codigoGrupo' => 10,
                        'nomeGrupo' => 'MATERIAIS ESCRITORIO',
                        'codigoClasse' => 1020,
                        'nomeClasse' => 'PAPELARIA',
                        'codigoPdm' => 102030,
                        'nomePdm' => 'CANETA ESFEROGRAFICA',
                        'codigoItem' => 99901,
                        'descricaoItem' => 'CANETA AZUL CLARO',
                        'itemSustentavel' => true,
                        'statusItem' => true,
                    ]
                ],
                'totalRegistros' => 1,
                'totalPaginas' => 1,
            ], 200),
            '*/modulo-servico/6_consultarItemServico*' => Http::response([
                'resultado' => [
                    [
                        'codigoGrupo' => 50,
                        'nomeGrupo' => 'SERVICOS TECNOLOGICOS',
                        'codigoClasse' => 5010,
                        'nomeClasse' => 'DESENVOLVIMENTO',
                        'codigoSubclasse' => 501020,
                        'nomeSubclasse' => 'FABRICA DE SOFTWARE',
                        'codigoServico' => 88802,
                        'descricaoServico' => 'DESENVOLVIMENTO PHP LARAVEL',
                        'statusServico' => true,
                    ]
                ],
                'totalRegistros' => 1,
                'totalPaginas' => 1,
            ], 200)
        ]);

        // 2. Clear previous cache state
        Cache::forget('etl_progress');

        // 3. Run the Artisan command with page limit of 1
        $this->artisan('catalog:sync-etl', ['--limit-pages' => 1])
            ->assertExitCode(0);

        // 4. Verify Materials (CATMAT) item was Upserted correctly
        $this->assertDatabaseHas('catalog_items', [
            'item_code' => '99901',
            'description' => 'CANETA AZUL CLARO',
            'is_sustainable' => true,
            'is_active' => true,
        ]);

        // 5. Verify Taxonomy Node mapping (Grupo, Classe, PDM) for Materials
        $this->assertDatabaseHas('gov_catalog_taxonomies', [
            'catalog_type' => 'material',
            'level_name' => 'group',
            'code' => '10',
            'description' => 'MATERIAIS ESCRITORIO',
        ]);
        $this->assertDatabaseHas('gov_catalog_taxonomies', [
            'catalog_type' => 'material',
            'level_name' => 'class',
            'code' => '1020',
            'description' => 'PAPELARIA',
            'parent_code' => '10',
        ]);
        $this->assertDatabaseHas('gov_catalog_taxonomies', [
            'catalog_type' => 'material',
            'level_name' => 'pdm',
            'code' => '102030',
            'description' => 'CANETA ESFEROGRAFICA',
            'parent_code' => '1020',
        ]);

        // 6. Verify Services (CATSER) item was Upserted correctly
        $this->assertDatabaseHas('catalog_services', [
            'service_code' => '88802',
            'description' => 'DESENVOLVIMENTO PHP LARAVEL',
            'is_active' => true,
        ]);

        // 7. Verify Taxonomy Node mapping for Services
        $this->assertDatabaseHas('gov_catalog_taxonomies', [
            'catalog_type' => 'service',
            'level_name' => 'group',
            'code' => '50',
            'description' => 'SERVICOS TECNOLOGICOS',
        ]);
        $this->assertDatabaseHas('gov_catalog_taxonomies', [
            'catalog_type' => 'service',
            'level_name' => 'class',
            'code' => '5010',
            'description' => 'DESENVOLVIMENTO',
            'parent_code' => '50',
        ]);
        $this->assertDatabaseHas('gov_catalog_taxonomies', [
            'catalog_type' => 'service',
            'level_name' => 'subclass',
            'code' => '501020',
            'description' => 'FABRICA DE SOFTWARE',
            'parent_code' => '5010',
        ]);

        // 8. Verify Progress Cache is updated with 'completed'
        $progress = Cache::get('etl_progress');
        $this->assertNotNull($progress);
        $this->assertEquals('completed', $progress['status']);
        $this->assertEquals(100, $progress['progress']);
        $this->assertEquals(1, $progress['processed_materials']);
        $this->assertEquals(1, $progress['processed_services']);
    }
}
