<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlanningModuleRequest;
use App\Models\Planning\ProcurementItem;
use App\Models\Planning\ProcurementRequest;
use App\Models\Planning\ProcurementStudy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ModuleOneController extends Controller
{
    public function create(): View
    {
        return view('planning.module-one.create', [
            'thresholds' => config('compras.lei_14133.dispensa.art75'),
        ]);
    }

    public function store(StorePlanningModuleRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $procurementRequest = DB::transaction(function () use ($validated): ProcurementRequest {
            $requestData = Arr::only($validated, [
                'reference_code',
                'title',
                'object_summary',
                'priority_level',
                'need_justification',
                'priority_justification',
                'planned_conclusion_at',
                'linked_request',
                'environmental_impacts',
                'reverse_logistics',
                'municipal_policy_applies',
                'municipal_policy_justification',
                'requisition_unit',
                'requester_name',
                'requester_cpf',
                'requester_role',
                'responsible_name',
                'responsible_cpf',
                'responsible_role',
            ]);

            $requestData['status'] = 'draft';

            $procurementRequest = ProcurementRequest::create($requestData);

            $studyData = $validated['study'];
            $items = $validated['items'];

            if (! array_key_exists('estimated_total_cost', $studyData) || $studyData['estimated_total_cost'] === null) {
                $studyData['estimated_total_cost'] = collect($items)->sum(function (array $item): float {
                    $quantity = (float) ($item['quantity'] ?? 0);
                    $unitValue = (float) ($item['unit_value'] ?? 0);

                    return round($quantity * $unitValue, 2);
                });
            }

            $studyData['procurement_request_id'] = $procurementRequest->id;
            $studyData['team_signatures'] = $studyData['team_signatures'] ?? [];
            $studyData['municipal_policy_applies'] = (bool) ($studyData['municipal_policy_applies'] ?? false);

            $study = ProcurementStudy::create($studyData);

            foreach ($items as $item) {
                ProcurementItem::create([
                    'procurement_request_id' => $procurementRequest->id,
                    'procurement_study_id' => $study->id,
                    'item_type' => $item['item_type'],
                    'catalog_code' => $item['catalog_code'] ?? null,
                    'description' => $item['description'],
                    'unit' => $item['unit'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_value' => $item['unit_value'] ?? null,
                    'total_value' => isset($item['unit_value']) ? round(((float) $item['quantity']) * ((float) $item['unit_value']), 2) : null,
                    'source_system' => $item['source_system'] ?? null,
                    'source_reference' => $item['source_reference'] ?? null,
                    'is_sustainable' => (bool) ($item['is_sustainable'] ?? false),
                    'notes' => $item['notes'] ?? null,
                    'metadata' => [
                        'filled_via' => 'module-one',
                    ],
                ]);
            }

            return $procurementRequest;
        });

        return redirect()
            ->route('planning.module-one.show', $procurementRequest)
            ->with('status', 'Módulo 1 salvo com sucesso.');
    }

    public function show(ProcurementRequest $procurementRequest): View
    {
        $procurementRequest->load(['items', 'studies.items']);

        return view('planning.module-one.show', [
            'procurementRequest' => $procurementRequest,
            'study' => $procurementRequest->studies->first(),
            'items' => $procurementRequest->items,
            'thresholds' => config('compras.lei_14133.dispensa.art75'),
        ]);
    }
}