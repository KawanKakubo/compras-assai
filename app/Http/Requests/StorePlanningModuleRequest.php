<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanningModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'municipal_policy_applies' => $this->boolean('municipal_policy_applies'),
            'has_environmental_impact' => $this->boolean('has_environmental_impact'),
            'has_reverse_logistics' => $this->boolean('has_reverse_logistics'),
            'study.municipal_policy_applies' => $this->boolean('study.municipal_policy_applies'),
            'study.is_in_pca' => $this->boolean('study.is_in_pca'),
        ]);
    }

    public function rules(): array
    {
        return [
            // ── Step 1: Identification ──────────────────────────
            'reference_code' => ['nullable', 'string', 'max:50', 'unique:procurement_requests,reference_code'],
            'secretaria' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'object_summary' => ['required', 'string', 'max:200'],
            'priority_level' => ['required', 'in:low,medium,high'],
            'priority_justification' => ['nullable', 'required_if:priority_level,high', 'string'],
            'planned_conclusion_at' => ['nullable', 'date'],
            'requester_name' => ['required', 'string', 'max:255'],
            'requester_cpf' => ['required', 'string', 'max:14'],
            'requester_role' => ['required', 'string', 'max:255'],
            'responsible_name' => ['required', 'string', 'max:255'],
            'responsible_cpf' => ['required', 'string', 'max:14'],
            'responsible_role' => ['required', 'string', 'max:255'],

            // ── Step 2: Need & Justification ────────────────────
            'need_justification' => ['required', 'string'],
            'linked_request' => ['nullable', 'string'],
            'has_environmental_impact' => ['nullable', 'boolean'],
            'environmental_impacts' => ['nullable', 'required_if:has_environmental_impact,1', 'string'],
            'has_reverse_logistics' => ['nullable', 'boolean'],
            'reverse_logistics' => ['nullable', 'required_if:has_reverse_logistics,1', 'string'],
            'demand_memory_calculation' => ['nullable', 'string'],
            'municipal_policy_applies' => ['nullable', 'boolean'],
            'municipal_policy_justification' => ['nullable', 'required_if:municipal_policy_applies,1', 'string'],
            'municipal_policy_details' => ['nullable', 'array'],
            'requisition_unit' => ['nullable', 'string', 'max:255'],

            // ── Step 3: Items ───────────────────────────────────
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_type' => ['required', 'in:material,service'],
            'items.*.catalog_code' => ['nullable', 'string', 'max:50'],
            'items.*.catmat_group' => ['nullable', 'string', 'max:255'],
            'items.*.catmat_class' => ['nullable', 'string', 'max:255'],
            'items.*.catmat_pdm' => ['nullable', 'string', 'max:255'],
            'items.*.description' => ['required', 'string'],
            'items.*.catmat_description' => ['nullable', 'string'],
            'items.*.detailed_description' => ['nullable', 'string'],
            'items.*.specification_justification' => ['nullable', 'string', 'required_with:items.*.detailed_description'],
            'items.*.unit' => ['nullable', 'string', 'max:32'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_value' => ['nullable', 'numeric', 'min:0'],
            'items.*.price_median' => ['nullable', 'numeric', 'min:0'],
            'items.*.price_min' => ['nullable', 'numeric', 'min:0'],
            'items.*.price_max' => ['nullable', 'numeric', 'min:0'],
            'items.*.price_sample_count' => ['nullable', 'integer', 'min:0'],
            'items.*.source_system' => ['nullable', 'string', 'max:50'],
            'items.*.source_reference' => ['nullable', 'string', 'max:255'],
            'items.*.is_sustainable' => ['nullable', 'boolean'],
            'items.*.notes' => ['nullable', 'string'],
            'items.*.memory_calculation' => ['nullable', 'string'],

            // ── Step 5: ETP Study ───────────────────────────────
            'study' => ['required', 'array'],
            'study.is_in_pca' => ['nullable', 'boolean'],
            'study.pca_reference' => ['nullable', 'string', 'max:255'],
            'study.pca_description' => ['nullable', 'string'],
            'study.need_description' => ['nullable', 'string'],
            'study.motivation' => ['nullable', 'string'],
            'study.prerequisites' => ['nullable', 'string'],
            'study.correlated_contracts' => ['nullable', 'string'],
            'study.solution_requirements' => ['nullable', 'string'],
            'study.demand_estimate' => ['nullable', 'string'],
            'study.environmental_analysis' => ['nullable', 'string'],
            'study.solution_mapping' => ['nullable', 'string'],
            'study.discarded_solutions' => ['nullable', 'string'],
            'study.parceling_justification' => ['nullable', 'string'],
            'study.chosen_solution' => ['nullable', 'string'],
            'study.estimated_total_cost' => ['nullable', 'numeric', 'min:0'],
            'study.expected_results' => ['nullable', 'string'],

            // ── Step 6: Viability ───────────────────────────────
            'study.viability_analysis' => ['nullable', 'string'],
            'study.viability_technical' => ['nullable', 'string'],
            'study.viability_operational' => ['nullable', 'string'],
            'study.viability_economic' => ['nullable', 'string'],
            'study.viability_budgetary' => ['nullable', 'string'],
            'study.viability_legal' => ['nullable', 'string'],
            'study.municipal_policy_applies' => ['nullable', 'boolean'],
            'study.municipal_policy_analysis' => ['nullable', 'string'],
            'study.municipal_program_eligible' => ['nullable', 'boolean'],
            'study.municipal_program_segment' => ['nullable', 'string'],
            'study.municipal_program_me_epp_compatible' => ['nullable', 'boolean'],
            'study.municipal_program_within_limits' => ['nullable', 'boolean'],
            'study.municipal_program_local_suppliers' => ['nullable', 'array'],
            'study.municipal_program_competitive' => ['nullable', 'boolean'],
            'study.municipal_program_advantageous' => ['nullable', 'string', 'in:advantageous,neutral,disadvantageous'],
            'study.municipal_program_recommendation' => ['nullable', 'string'],
            'study.municipal_program_justification' => ['nullable', 'string'],
            'study.viability_decision' => ['required', 'in:viable,viable_with_restrictions,not_viable'],
            'study.viability_justification' => ['nullable', 'string'],
            'study.team_signatures' => ['nullable', 'array'],
            'study.team_signatures.*.name' => ['required_with:study.team_signatures', 'string', 'max:255'],
            'study.team_signatures.*.cpf' => ['nullable', 'string', 'max:14'],
            'study.team_signatures.*.role' => ['nullable', 'string', 'max:255'],
            'study.team_signatures.*.signature_date' => ['nullable', 'date'],
            'study.planning_team_portaria' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'secretaria.required' => 'Selecione a Secretaria Requisitante.',
            'title.required' => 'Informe o título da demanda.',
            'object_summary.required' => 'Informe o resumo do objeto (máx. 200 caracteres).',
            'object_summary.max' => 'O resumo do objeto deve ter no máximo 200 caracteres.',
            'priority_level.required' => 'Selecione o grau de prioridade.',
            'need_justification.required' => 'Informe a justificativa da necessidade.',
            'items.required' => 'Adicione pelo menos um item à demanda.',
            'items.min' => 'Adicione pelo menos um item à demanda.',
            'items.*.description.required' => 'Informe a descrição de cada item.',
            'items.*.quantity.required' => 'Informe a quantidade de cada item.',
            'study.viability_decision.required' => 'Selecione a decisão de viabilidade.',
            'priority_justification.required_if' => 'A justificativa é obrigatória quando a prioridade é Alta.',
            'items.*.specification_justification.required_with' => 'A justificativa técnica é obrigatória quando especificações detalhadas são adicionadas para evitar direcionamento de marca.',
        ];
    }
}