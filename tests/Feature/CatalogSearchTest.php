<?php

namespace Tests\Feature;

use App\Models\CatalogItem;
use App\Models\CatalogService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CatalogSearchTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create an authenticated user
        $this->user = User::factory()->create(['role' => 'secretaria']);
    }

    /** @test */
    public function test_it_returns_local_material_items_successfully()
    {
        // Seed local data
        CatalogItem::create([
            'item_code' => 12345,
            'description' => 'CANETA AZUL TESTE',
            'pdm_code' => 101,
            'pdm_name' => 'CANETA',
            'class_code' => 75,
            'class_name' => 'PAPELARIA',
            'group_code' => 75,
            'group_name' => 'ESCRITORIO',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/compras-gov/material/items?descricaoItem=caneta');

        $response->assertStatus(200)
            ->assertJsonFragment(['descricaoItem' => 'CANETA AZUL TESTE'])
            ->assertJsonFragment(['source' => 'local']);
    }

    /** @test */
    public function test_it_is_case_insensitive_for_local_search()
    {
        CatalogItem::create([
            'item_code' => 67890,
            'description' => 'CADERNO UNIVERSITARIO',
            'pdm_code' => 102,
            'pdm_name' => 'CADERNO',
            'class_code' => 75,
            'class_name' => 'PAPELARIA',
            'group_code' => 75,
            'group_name' => 'ESCRITORIO',
        ]);

        // Test with uppercase
        $responseUpper = $this->actingAs($this->user)
            ->getJson('/api/compras-gov/material/items?descricaoItem=CADERNO');
        
        $responseUpper->assertStatus(200)
            ->assertJsonFragment(['descricaoItem' => 'CADERNO UNIVERSITARIO']);

        // Test with lowercase
        $responseLower = $this->actingAs($this->user)
            ->getJson('/api/compras-gov/material/items?descricaoItem=caderno');
            
        $responseLower->assertStatus(200)
            ->assertJsonFragment(['descricaoItem' => 'CADERNO UNIVERSITARIO']);
    }

    /** @test */
    public function test_it_falls_back_to_remote_api_when_local_item_not_found()
    {
        // Fake remote API response
        Http::fake([
            '*/modulo-material/4_consultarItemMaterial*' => Http::response([
                'resultado' => [
                    ['codigoItem' => 99999, 'descricaoItem' => 'ITEM DA API GOVERNO']
                ],
                'totalRegistros' => 1
            ], 200)
        ]);

        // Search for something that doesn't exist locally
        $response = $this->actingAs($this->user)
            ->getJson('/api/compras-gov/material/items?descricaoItem=item_inexistente_local');

        $response->assertStatus(200)
            ->assertJsonFragment(['descricaoItem' => 'ITEM DA API GOVERNO']);
            
        // Remote results don't have the 'source' => 'local' field we added
        $response->assertJsonMissing(['source' => 'local']);
    }

    /** @test */
    public function test_it_returns_local_service_items_successfully()
    {
        CatalogService::create([
            'service_code' => 555,
            'description' => 'SERVICO DE JARDINAGEM LOCAL',
            'group_code' => 10,
            'group_name' => 'MANUTENCAO',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/compras-gov/service/items?descricaoItem=jardinagem');

        $response->assertStatus(200)
            ->assertJsonFragment(['descricaoServico' => 'SERVICO DE JARDINAGEM LOCAL'])
            ->assertJsonFragment(['source' => 'local']);
    }
}
