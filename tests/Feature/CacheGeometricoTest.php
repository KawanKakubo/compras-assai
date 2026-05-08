<?php

namespace Tests\Feature;

use App\Models\CatalogItem;
use App\Models\CatalogService;
use App\Models\User;
use App\Services\ComprasGov\MaterialCatalogService;
use App\Services\ComprasGov\ServiceCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CacheGeometricoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test only admins can access Cacheamento Geométrico.
     */
    public function test_only_admins_can_access_cache_geometrico(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $elaborador = User::factory()->create(['role' => 'elaborador']);

        // Admin should be allowed
        $response = $this->actingAs($admin)->get(route('admin.cache-geometrico.index'));
        $response->assertStatus(200);
        $response->assertSee('Cacheamento Geométrico');

        // Elaborador should be forbidden
        $response = $this->actingAs($elaborador)->get(route('admin.cache-geometrico.index'));
        $response->assertStatus(403);
    }

    /**
     * Test admin can clear the local cache.
     */
    public function test_admin_can_clear_cache(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Seed some items
        CatalogItem::create([
            'item_code' => '999111',
            'description' => 'TEST ITEM',
            'pdm_code' => 1234,
            'pdm_name' => 'TEST PDM',
            'class_code' => 7530,
            'class_name' => 'MOCK CLASS',
            'group_code' => 75,
            'group_name' => 'MOCK GROUP',
        ]);

        CatalogService::create([
            'service_code' => '999222',
            'description' => 'TEST SERVICE',
            'group_code' => 1,
            'group_name' => 'MANUTENÇÃO',
        ]);

        $this->assertDatabaseCount('catalog_items', 1);
        $this->assertDatabaseCount('catalog_services', 1);

        // Call clear
        $response = $this->actingAs($admin)->post(route('admin.cache-geometrico.clear'));
        $response->assertRedirect(route('admin.cache-geometrico.index'));

        $this->assertDatabaseCount('catalog_items', 0);
        $this->assertDatabaseCount('catalog_services', 0);
    }

    /**
     * Test local search priority (Hierarquia de busca prioriza base local).
     */
    public function test_local_search_priority_is_enforced(): void
    {
        // Seed our local cache with specific items
        CatalogItem::create([
            'item_code' => '111222',
            'description' => 'MESA ESCRITORIO PREMIUM',
            'pdm_code' => 500,
            'pdm_name' => 'MESA',
            'class_code' => 7110,
            'class_name' => 'MOBILIÁRIO',
            'group_code' => 71,
            'group_name' => 'MOBILIÁRIOS',
        ]);

        CatalogService::create([
            'service_code' => '333444',
            'description' => 'PINTURA RESIDENCIAL MUNICIPAL',
            'group_code' => 2,
            'group_name' => 'SERVIÇOS GERAIS',
        ]);

        // Resolve services
        $materialService = app(MaterialCatalogService::class);
        $serviceService = app(ServiceCatalogService::class);

        // 1. Search materials. Since it exists locally, it should return 'local' source
        $materialResults = $materialService->searchItems([
            'codigoPdm' => 500,
            'descricaoItem' => 'MESA'
        ]);

        $this->assertEquals('local', $materialResults['source']);
        $this->assertCount(1, $materialResults['resultado']);
        $this->assertEquals('MESA ESCRITORIO PREMIUM', $materialResults['resultado'][0]['descricaoItem']);

        // 2. Search services. Since it exists locally, it should return 'local' source
        $serviceResults = $serviceService->searchItems([
            'codigoSubclasse' => '333444',
            'descricaoItem' => 'PINTURA'
        ]);

        $this->assertEquals('local', $serviceResults['source']);
        $this->assertCount(1, $serviceResults['resultado']);
        $this->assertEquals('PINTURA RESIDENCIAL MUNICIPAL', $serviceResults['resultado'][0]['descricaoServico']);
    }

    /**
     * Test sync command initiates successfully and returns JSON.
     */
    public function test_sync_initiates_successfully(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson(route('admin.cache-geometrico.sync'));
        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'message' => 'ETL massivo do catálogo iniciado em segundo plano com sucesso!'
        ]);
    }

    /**
     * Test progress endpoint.
     */
    public function test_progress_endpoint_returns_valid_json(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->getJson(route('admin.cache-geometrico.progress'));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'progress',
            'message',
            'processed_materials',
            'processed_services',
            'current_page',
            'total_pages',
            'logs',
            'materials_count',
            'services_count',
            'taxonomy_count'
        ]);
    }
}
