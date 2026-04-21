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
            'study.municipal_policy_applies' => $this->boolean('study.municipal_policy_applies'),
        ]);
    }

    public function rules(): array
    {
        return [
            'reference_code' => ['nullable', 'string', 'max:50', 'unique:procurement_requests,reference_code'],
            'title' => ['required', 'string', 'max:255'],
            'object_summary' => ['required', 'string', 'max:200'],
            'priority_level' => ['required', 'in:low,medium,high'],
            'need_justification' => ['required', 'string'],
            'priority_justification' => ['nullable', 'string'],
            'planned_conclusion_at' => ['nullable', 'date'],
            'linked_request' => ['nullable', 'string'],
            'environmental_impacts' => ['nullable', 'string'],
            'reverse_logistics' => ['nullable', 'string'],
            'municipal_policy_applies' => ['nullable', 'boolean'],
            'municipal_policy_justification' => ['required_if:municipal_policy_applies,1', 'nullable', 'string'],
            'requisition_unit' => ['nullable', 'string', 'max:255'],
            'requester_name' => ['nullable', 'string', 'max:255'],
            'requester_cpf' => ['nullable', 'string', 'max:14'],
            'requester_role' => ['nullable', 'string', 'max:255'],
            'responsible_name' => ['nullable', 'string', 'max:255'],
            'responsible_cpf' => ['nullable', 'string', 'max:14'],
            'responsible_role' => ['nullable', 'string', 'max:255'],
            'study' => ['required', 'array'],
            'study.is_in_pca' => ['nullable', 'boolean'],
            'study.pca_reference' => ['nullable', 'string', 'max:255'],
            'study.pca_description' => ['nullable', 'string'],
            'study.need_description' => ['required', 'string'],
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
            'study.viability_analysis' => ['nullable', 'string'],
            'study.municipal_policy_applies' => ['nullable', 'boolean'],
            'study.municipal_policy_analysis' => ['nullable', 'string'],
            'study.viability_decision' => ['required', 'in:viable,viable_with_restrictions,not_viable'],
            'study.viability_justification' => ['nullable', 'string'],
            'study.team_signatures' => ['nullable', 'array'],
            'study.team_signatures.*.name' => ['required_with:study.team_signatures', 'string', 'max:255'],
            'study.team_signatures.*.role' => ['nullable', 'string', 'max:255'],
            'study.team_signatures.*.signature_date' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_type' => ['required', 'in:material,service'],
            'items.*.catalog_code' => ['nullable', 'string', 'max:50'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.unit' => ['nullable', 'string', 'max:32'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_value' => ['nullable', 'numeric', 'min:0'],
            'items.*.source_system' => ['nullable', 'string', 'max:50'],
            'items.*.source_reference' => ['nullable', 'string', 'max:255'],
            'items.*.is_sustainable' => ['nullable', 'boolean'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}