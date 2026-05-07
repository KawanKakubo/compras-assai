<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Planning\ProcurementItem;
use App\Models\Planning\ProcurementRequest;
use App\Models\Planning\ProcurementStudy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleOnePlanningTest extends TestCase
{
    use RefreshDatabase;

    private function createElaboradorUser(): User
    {
        return User::factory()->create([
            'role' => 'elaborador',
            'secretaria_acronym' => 'SAUDE',
            'secretaria_name' => 'Secretaria Municipal de Saúde',
        ]);
    }

    private function getValidPayload(): array
    {
        return [
            'reference_code' => 'REQ-2026-001',
            'title' => 'Aquisição de cadeiras ergonômicas',
            'object_summary' => 'Cadeiras ergonômicas para postos administrativos.',
            'priority_level' => 'high',
            'need_justification' => 'Substituir cadeiras com desgaste e reduzir riscos ergonômicos.',
            'priority_justification' => 'A situação impacta a operação diária.',
            'planned_conclusion_at' => '2026-06-30',
            'linked_request' => 'Demanda da coordenação administrativa.',
            'environmental_impacts' => 'Produto com maior durabilidade reduz descarte.',
            'reverse_logistics' => 'Fornecedor deve prever recolhimento de embalagens.',
            'municipal_policy_applies' => 0,
            'secretaria' => 'SAUDE',
            'requisition_unit' => 'Coordenação Administrativa',
            'requester_name' => 'Maria Requisitante',
            'requester_cpf' => '000.000.000-00',
            'requester_role' => 'Analista',
            'responsible_name' => 'João Responsável',
            'responsible_cpf' => '111.111.111-11',
            'responsible_role' => 'Gerente',
            'study' => [
                'is_in_pca' => 1,
                'pca_reference' => 'PCA-2026-01',
                'pca_description' => 'Item previsto no PCA.',
                'need_description' => 'Necessidade de adequação ergonômica dos postos.',
                'motivation' => 'Melhorar a saúde ocupacional da equipe.',
                'prerequisites' => 'Espaço físico disponível e entrega fracionada.',
                'correlated_contracts' => 'Contrato anterior encerrado em 2025.',
                'solution_requirements' => 'Ajuste lombar, altura regulável e garantia ampliada.',
                'demand_estimate' => '10 unidades para o primeiro ciclo.',
                'environmental_analysis' => 'Produto com maior vida útil e menor descarte.',
                'solution_mapping' => 'Aquisição por catálogo com pesquisa histórica.',
                'discarded_solutions' => 'Manutenção das cadeiras atuais não atende.',
                'parceling_justification' => 'Compra em lote por unidade administrativa.',
                'chosen_solution' => 'Cadeiras ergonômicas com garantia de 5 anos.',
                'expected_results' => 'Redução de queixas e melhoria postural.',
                'viability_analysis' => 'Mercado atende com ampla oferta.',
                'municipal_policy_applies' => 0,
                'municipal_policy_analysis' => '',
                'viability_decision' => 'viable',
                'viability_justification' => 'Existe solução adequada no mercado sob aspectos operacionais.',
                'team_signatures' => [
                    ['name' => 'Maria Requisitante', 'role' => 'Requisitante', 'signature_date' => '2026-04-21'],
                    ['name' => 'João Responsável', 'role' => 'Aprovador', 'signature_date' => '2026-04-21'],
                ],
            ],
            'items' => [
                [
                    'item_type' => 'material',
                    'catalog_code' => '206504',
                    'description' => 'CADEIRA ERGONÔMICA',
                    'unit' => 'UN',
                    'quantity' => 10,
                    'unit_value' => 899.90,
                    'source_system' => 'compras_gov',
                    'source_reference' => '2026/001',
                    'is_sustainable' => 1,
                    'notes' => 'Priorizar encosto regulável.',
                ],
            ],
        ];
    }

    public function test_module_one_page_is_accessible(): void
    {
        $user = $this->createElaboradorUser();

        $response = $this->actingAs($user)->get(route('planning.module-one.create'));

        $response->assertOk();
        $response->assertSee('Assistente Inteligente de Planejamento da Contratação');
    }

    public function test_module_one_submission_persists_request_study_and_items(): void
    {
        $user = $this->createElaboradorUser();
        $payload = $this->getValidPayload();

        $response = $this->actingAs($user)->post(route('planning.module-one.store'), $payload);

        $response->assertRedirect();

        $request = ProcurementRequest::query()->firstOrFail();
        $study = ProcurementStudy::query()->firstOrFail();
        $item = ProcurementItem::query()->firstOrFail();

        $this->assertSame('REQ-2026-001', $request->reference_code);
        $this->assertSame('Aquisição de cadeiras ergonômicas', $request->title);
        $this->assertSame('10 unidades para o primeiro ciclo.', $study->demand_estimate);
        $this->assertSame('Espaço físico disponível e entrega fracionada.', $study->prerequisites);
        $this->assertSame('Contrato anterior encerrado em 2025.', $study->correlated_contracts);
        $this->assertSame('Existe solução adequada no mercado sob aspectos operacionais.', $study->viability_justification);
        $this->assertSame('8999.00', $study->estimated_total_cost);
        $this->assertSame('206504', $item->catalog_code);
        $this->assertSame('8999.00', $item->total_value);

        $this->actingAs($user)->get(route('planning.module-one.show', $request))
            ->assertOk()
            ->assertSee('Estudo Técnico Preliminar (ETP)');
    }

    public function test_module_one_requires_at_least_one_item(): void
    {
        $user = $this->createElaboradorUser();

        $payload = [
            'title' => 'Teste',
            'object_summary' => 'Resumo do objeto.',
            'priority_level' => 'medium',
            'need_justification' => 'Necessidade.',
            'secretaria' => 'SAUDE',
            'study' => [
                'need_description' => 'Descrição da necessidade.',
                'viability_decision' => 'viable',
            ],
        ];

        $this->actingAs($user)->postJson(route('planning.module-one.store'), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_module_one_downloads_sd_successfully(): void
    {
        $user = $this->createElaboradorUser();
        $payload = $this->getValidPayload();

        $this->actingAs($user)->post(route('planning.module-one.store'), $payload);
        $request = ProcurementRequest::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('planning.module-one.download-sd', $request));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/octet-stream');
    }

    public function test_module_one_downloads_etp_successfully(): void
    {
        $user = $this->createElaboradorUser();
        $payload = $this->getValidPayload();

        $this->actingAs($user)->post(route('planning.module-one.store'), $payload);
        $request = ProcurementRequest::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('planning.module-one.download-etp', $request));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/octet-stream');
    }
}