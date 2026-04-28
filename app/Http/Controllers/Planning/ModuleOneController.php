<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlanningModuleRequest;
use App\Models\Planning\ProcurementItem;
use App\Models\Planning\ProcurementRequest;
use App\Models\Planning\ProcurementStudy;
use App\Services\Planning\DocumentTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ModuleOneController extends Controller
{
    public function create(): View
    {
        $user = auth()->user();
        $acronym = $user?->secretaria_acronym;

        return view('planning.module-one.create', [
            'thresholds' => config('compras.lei_14133.dispensa.art75'),
            'secretarias' => config('compras.secretarias'),
            'prioridades' => config('compras.prioridades'),
            'unidadesComuns' => config('compras.unidades_comuns'),
            'programaMunicipal' => config('compras.programa_municipal'),
            'nextReferenceCode' => ProcurementRequest::generateReferenceCode($acronym),
            'currentUser' => $user,
        ]);
    }

    public function store(StorePlanningModuleRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $procurementRequest = DB::transaction(function () use ($validated): ProcurementRequest {
            // ── SD (Solicitação de Demanda) ──────────────────────
            $requestData = Arr::only($validated, [
                'reference_code',
                'secretaria',
                'title',
                'object_summary',
                'priority_level',
                'need_justification',
                'priority_justification',
                'planned_conclusion_at',
                'linked_request',
                'environmental_impacts',
                'reverse_logistics',
                'has_environmental_impact',
                'has_reverse_logistics',
                'demand_memory_calculation',
                'municipal_policy_applies',
                'municipal_policy_justification',
                'municipal_policy_details',
                'requisition_unit',
                'requester_name',
                'requester_cpf',
                'requester_role',
                'responsible_name',
                'responsible_cpf',
                'responsible_role',
            ]);

            if (empty($requestData['reference_code'])) {
                $requestData['reference_code'] = ProcurementRequest::generateReferenceCode();
            }

            $requestData['user_id'] = auth()->id();
            $requestData['status'] = ProcurementRequest::STATUS_RASCUNHO;
            $requestData['requisition_unit'] = $validated['secretaria'] ?? null;

            $procurementRequest = ProcurementRequest::create($requestData);

            // ── ETP (Estudo Técnico Preliminar) ─────────────────
            $studyData = $validated['study'];
            $items = $validated['items'];

            // Calculate estimated total from items if not provided
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

            // Copy environmental data from SD to ETP if not separately provided
            if (empty($studyData['environmental_analysis']) && ! empty($validated['environmental_impacts'])) {
                $studyData['environmental_analysis'] = $validated['environmental_impacts'];
            }

            // Copy need description from SD to ETP if not separately provided
            if (empty($studyData['need_description']) && ! empty($validated['need_justification'])) {
                $studyData['need_description'] = $validated['need_justification'];
            }

            $study = ProcurementStudy::create($studyData);

            // ── Items ─────────────────────────────────────────────
            foreach ($items as $item) {
                ProcurementItem::create([
                    'procurement_request_id' => $procurementRequest->id,
                    'procurement_study_id' => $study->id,
                    'item_type' => $item['item_type'],
                    'catalog_code' => $item['catalog_code'] ?? null,
                    'catmat_group' => $item['catmat_group'] ?? null,
                    'catmat_class' => $item['catmat_class'] ?? null,
                    'catmat_pdm' => $item['catmat_pdm'] ?? null,
                    'description' => $item['description'],
                    'unit' => $item['unit'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_value' => $item['unit_value'] ?? null,
                    'total_value' => isset($item['unit_value']) ? round(((float) $item['quantity']) * ((float) $item['unit_value']), 2) : null,
                    'price_median' => $item['price_median'] ?? null,
                    'price_min' => $item['price_min'] ?? null,
                    'price_max' => $item['price_max'] ?? null,
                    'price_sample_count' => $item['price_sample_count'] ?? null,
                    'source_system' => $item['source_system'] ?? 'compras_gov',
                    'source_reference' => $item['source_reference'] ?? null,
                    'is_sustainable' => (bool) ($item['is_sustainable'] ?? false),
                    'notes' => $item['notes'] ?? null,
                    'memory_calculation' => $item['memory_calculation'] ?? null,
                    'metadata' => [
                        'filled_via' => 'module-one-wizard',
                    ],
                ]);
            }

            return $procurementRequest;
        });

        return redirect()
            ->route('planning.module-one.show', $procurementRequest)
            ->with('status', 'Solicitação registrada com sucesso. Documentos prontos para download.');
    }

    public function show(ProcurementRequest $procurementRequest): View
    {
        $procurementRequest->load(['items', 'studies.items']);

        $study = $procurementRequest->studies->first();
        $items = $procurementRequest->items;

        // Determine legal framing based on estimated total
        $totalEstimated = $study?->estimated_total_cost ?? $items->sum('total_value');
        $thresholds = config('compras.lei_14133.dispensa.art75');

        // Check if any item is a service
        $hasServices = $items->where('item_type', 'service')->isNotEmpty();
        $hasMaterials = $items->where('item_type', 'material')->isNotEmpty();

        $legalFraming = 'licitacao'; // default
        if ($hasServices && $totalEstimated <= $thresholds['inciso_ii']) {
            $legalFraming = 'dispensa_servico';
        } elseif ($hasMaterials && ! $hasServices && $totalEstimated <= $thresholds['inciso_i']) {
            $legalFraming = 'dispensa_material';
        }

        return view('planning.module-one.show', [
            'procurementRequest' => $procurementRequest,
            'study' => $study,
            'items' => $items,
            'thresholds' => $thresholds,
            'totalEstimated' => $totalEstimated,
            'legalFraming' => $legalFraming,
            'secretarias' => config('compras.secretarias'),
            'prioridades' => config('compras.prioridades'),
            'programaMunicipal' => config('compras.programa_municipal'),
        ]);
    }

    public function downloadSd(ProcurementRequest $procurementRequest, DocumentTemplateService $documentTemplateService)
    {
        $filePath = $documentTemplateService->generateSd($procurementRequest);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function downloadEtp(ProcurementRequest $procurementRequest, DocumentTemplateService $documentTemplateService)
    {
        $procurementRequest->load(['studies']);
        if ($procurementRequest->studies->isEmpty()) {
            return back()->with('error', 'Estudo Técnico Preliminar não encontrado.');
        }
        $filePath = $documentTemplateService->generateEtp($procurementRequest);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function submitToGabinete(ProcurementRequest $procurementRequest): RedirectResponse
    {
        if (!$procurementRequest->canBeSubmitted()) {
            return redirect()->back()->with('error', 'A solicitação precisa estar assinada para ser enviada ao Gabinete.');
        }

        $procurementRequest->status = ProcurementRequest::STATUS_EM_ANALISE;
        $procurementRequest->save();

        return redirect()->back()->with('success', 'Solicitação enviada ao Gabinete com sucesso!');
    }
}