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
        $user = auth()->user();
        $acronym = $user?->isSecretaria() ? $user->secretaria_acronym : null;

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
            $requestData['status'] = ProcurementRequest::STATUS_AGUARDANDO_GABINETE;
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
            ->with('status', 'Solicitação de Demanda salva com sucesso! Documentos gerados.');
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

    public function downloadSd(ProcurementRequest $procurementRequest)
    {
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection();

        $phpWord->addTitleStyle(1, ['size' => 14, 'bold' => true, 'align' => 'center']);
        $phpWord->addTitleStyle(2, ['size' => 12, 'bold' => true, 'spaceBefore' => 200]);

        $section->addTitle('DOCUMENTO DE FORMALIZAÇÃO DE DEMANDA - DFD / SD', 1);
        $section->addTextBreak(1);

        $section->addText("Número da Solicitação: {$procurementRequest->reference_code}", ['bold' => true]);
        $section->addText("Data: " . $procurementRequest->created_at->format('d/m/Y'), ['bold' => true]);
        
        $secretarias = config('compras.secretarias');
        $secName = $secretarias[$procurementRequest->secretaria] ?? $procurementRequest->requisition_unit;
        $section->addText("Unidade Requisitante: {$secName}");
        $section->addTextBreak(1);

        $section->addTitle('1. JUSTIFICATIVA DA NECESSIDADE DA CONTRATAÇÃO', 2);
        $section->addText("A presente contratação faz-se necessária pelos seguintes motivos: {$procurementRequest->need_justification}");

        $section->addTitle('2. DESCRIÇÃO SUCINTA DO OBJETO', 2);
        $section->addText("{$procurementRequest->title}. {$procurementRequest->object_summary}");

        $section->addTitle('3. PRIORIDADE', 2);
        $prioridades = config('compras.prioridades');
        $prioName = $prioridades[$procurementRequest->priority_level] ?? 'Média';
        $section->addText("O grau de prioridade desta contratação é: {$prioName}.");
        if ($procurementRequest->priority_level === 'high') {
            $section->addText("Justificativa da Urgência: {$procurementRequest->priority_justification}");
        }

        $section->addTitle('4. SUSTENTABILIDADE E IMPACTOS', 2);
        $impacts = $procurementRequest->has_environmental_impact ? 'Sim. ' . $procurementRequest->environmental_impacts : 'Não há impactos ambientais significativos previstos.';
        $section->addText("Impactos Ambientais: {$impacts}");
        
        $logistics = $procurementRequest->has_reverse_logistics ? 'Sim. ' . $procurementRequest->reverse_logistics : 'Não se aplica logística reversa para este objeto.';
        $section->addText("Logística Reversa: {$logistics}");

        $fileName = "SD_{$procurementRequest->reference_code}.docx";
        $tempFile = storage_path('app/public/' . $fileName);
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);

        return response()->download($tempFile)->deleteFileAfterSend(true);
    }

    public function downloadEtp(ProcurementRequest $procurementRequest)
    {
        $procurementRequest->load(['studies']);
        $study = $procurementRequest->studies->first();

        if (!$study) {
            return back()->with('error', 'Estudo Técnico Preliminar não encontrado.');
        }

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection();

        $phpWord->addTitleStyle(1, ['size' => 14, 'bold' => true, 'align' => 'center']);
        $phpWord->addTitleStyle(2, ['size' => 12, 'bold' => true, 'spaceBefore' => 200]);

        $section->addTitle('ESTUDO TÉCNICO PRELIMINAR - ETP', 1);
        $section->addText('Lei nº 14.133/2021, Art. 18, § 1º', ['italic' => true], ['align' => 'center']);
        $section->addTextBreak(1);

        $section->addTitle('1. INFORMAÇÕES BÁSICAS', 2);
        $section->addText("Referência SD: {$procurementRequest->reference_code}");
        $section->addText("Objeto: {$procurementRequest->title}");
        $pca = $study->is_in_pca ? 'Sim. ' . $study->pca_reference : 'A demanda não consta no Plano de Contratações Anual vigente e requer inclusão.';
        $section->addText("Alinhamento ao PCA: {$pca}");

        $section->addTitle('2. DESCRIÇÃO DA NECESSIDADE DA CONTRATAÇÃO', 2);
        $section->addText($study->need_description ?? 'N/A');

        $section->addTitle('3. REQUISITOS DA CONTRATAÇÃO E RESULTADOS PRETENDIDOS', 2);
        $section->addText("Requisitos Técnicos: " . ($study->solution_requirements ?? 'Não há requisitos técnicos específicos além das descrições dos itens.'));
        $section->addText("Resultados Pretendidos: " . ($study->expected_results ?? 'Garantir o pleno funcionamento do órgão requisitante.'));

        $section->addTitle('4. LEVANTAMENTO DE MERCADO E JUSTIFICATIVA DE PARCELAMENTO', 2);
        $section->addText("Alternativas Analisadas: " . ($study->solution_mapping ?? 'N/A'));
        if ($study->discarded_solutions) {
            $section->addText("Soluções Descartadas: {$study->discarded_solutions}");
        }
        $section->addText("Justificativa sobre o Parcelamento: " . ($study->parceling_justification ?? 'N/A'));

        $fileName = "ETP_{$procurementRequest->reference_code}.docx";
        $tempFile = storage_path('app/public/' . $fileName);
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);

        return response()->download($tempFile)->deleteFileAfterSend(true);
    }

    public function downloadTr(ProcurementRequest $procurementRequest)
    {
        $procurementRequest->load(['items']);
        $items = $procurementRequest->items;

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection(['orientation' => 'landscape']); // TR is better in landscape for the table

        $phpWord->addTitleStyle(1, ['size' => 14, 'bold' => true, 'align' => 'center']);
        $phpWord->addTitleStyle(2, ['size' => 12, 'bold' => true, 'spaceBefore' => 200]);

        $section->addTitle('TERMO DE REFERÊNCIA - LEI 14.133/21', 1);
        $section->addTextBreak(1);

        $section->addTitle('1. DAS CONDIÇÕES GERAIS DA CONTRATAÇÃO', 2);
        $section->addText("Aquisição/Contratação de {$procurementRequest->title}, nos termos da tabela abaixo, conforme condições e exigências estabelecidas neste instrumento.");
        $section->addTextBreak(1);

        // Table Style
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50
        ];
        $phpWord->addTableStyle('TR Table', $tableStyle);
        $table = $section->addTable('TR Table');

        // Header
        $table->addRow();
        $table->addCell(500)->addText('ITEM', ['bold' => true]);
        $table->addCell(4000)->addText('ESPECIFICAÇÃO', ['bold' => true]);
        $table->addCell(1000)->addText('UNIDADE', ['bold' => true]);
        $table->addCell(1000)->addText('QUANTIDADE', ['bold' => true]);
        $table->addCell(1500)->addText('V. UNITÁRIO ESTIMADO (R$)', ['bold' => true]);
        $table->addCell(1500)->addText('V. TOTAL ESTIMADO (R$)', ['bold' => true]);

        $totalEstimated = 0;
        foreach ($items as $index => $item) {
            $table->addRow();
            $table->addCell(500)->addText($index + 1);
            $table->addCell(4000)->addText($item->description . "\nCATMAT/CATSER: " . $item->catalog_code);
            $table->addCell(1000)->addText($item->unit);
            $table->addCell(1000)->addText(number_format($item->quantity, 4, ',', '.'));
            $table->addCell(1500)->addText(number_format($item->unit_value, 2, ',', '.'));
            $table->addCell(1500)->addText(number_format($item->total_value, 2, ',', '.'));
            
            $totalEstimated += $item->total_value;
        }

        // Total Row
        $table->addRow();
        $table->addCell(8000, ['gridSpan' => 5])->addText('VALOR TOTAL ESTIMADO', ['bold' => true, 'align' => 'right']);
        $table->addCell(1500)->addText(number_format($totalEstimated, 2, ',', '.'), ['bold' => true]);

        $fileName = "TR_{$procurementRequest->reference_code}.docx";
        $tempFile = storage_path('app/public/' . $fileName);
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);

        return response()->download($tempFile)->deleteFileAfterSend(true);
    }
}