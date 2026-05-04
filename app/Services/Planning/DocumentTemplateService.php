<?php

namespace App\Services\Planning;

use App\Models\Planning\ProcurementRequest;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

class DocumentTemplateService
{
    /**
     * Gera o Documento de Formalização da Demanda (SD/DFD).
     */
    public function generateSd(ProcurementRequest $request): string
    {
        $request->loadMissing(['items']);
        $templatePath = base_path('docs/MODELO_SD.docx');
        $outputPath = storage_path('app/public/DFD_' . $request->reference_code . '_' . time() . '.docx');

        $secName = $request->secretaria;

        $data = [
            'placeholders' => [
                '{{referencia}}' => $request->reference_code,
                '{{objeto}}' => $request->object_summary,
                '{{justificativa}}' => $request->need_justification,
                'NOME:_autor' => ($request->requester_name ?? 'Não informado'),
                'CPF:_autor' => ($request->requester_cpf ?? ''),
                'CARGO/FUNÇÃO:_autor' => ($request->requester_role ?? ''),
                'NOME:_secretario' => ($request->responsible_name ?? 'Não informado'),
                'CPF:_secretario' => ($request->responsible_cpf ?? ''),
                'CARGO/FUNÇÃO:_secretario' => ($request->responsible_role ?? ''),
                
                '___SECRETARIA___' => $secName,
                '___ANO___' => date('Y'),
                '___CODIGO_REF___' => $request->reference_code,
            ],
            'instructions' => [
                'Data prevista para conclusão do processo' => $request->planned_conclusion_at ? $request->planned_conclusion_at->format('d/m/Y') : 'Não informada',
                'Descrição sucinta do objeto' => $request->object_summary,
                'Grau de prioridade da compra ou contratação' => $request->priority_level === 'high' ? 'Alta' : ($request->priority_level === 'medium' ? 'Média' : 'Baixa'),
                'Justificativa da necessidade da contratação' => $request->need_justification,
                'Indicação de vinculação ou dependência com objeto de outro documento de formalização de demanda' => $request->linked_request ?? 'Não se aplica',
                'IMPACTOS AMBIENTAIS' => $request->environmental_impacts ?? 'Não foram identificados impactos significativos.',
                'LOGISTICA REVERSA' => $request->reverse_logistics ?? 'Não se aplica.',
                'Aplica:' => $request->municipal_policy_applies ? 'Sim' : 'Não',
                'justificativa para a aplicação' => $request->municipal_policy_justification ?? 'Não se aplica.',
                'Requisitante (Unidade/Setor/Depto)' => $request->requisition_unit ?? $secName,
                'Programa Compras ____?' => $request->studies->first()?->municipal_program_eligible ? 'Sim' : 'Não',
            ],
            'items' => $request->items->map(fn($item) => [
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit' => $item->unit,
                'unit_value' => $item->unit_value,
            ])->toArray(),
        ];

        return $this->callPythonGenerator($templatePath, $outputPath, $data);
    }

    /**
     * Gera o Estudo Técnico Preliminar (ETP).
     */
    public function generateEtp(ProcurementRequest $request): string
    {
        $request->loadMissing(['items', 'studies', 'user']);
        $templatePath = base_path('docs/MODELO_ETP.docx');
        $outputPath = storage_path('app/public/ETP_' . $request->reference_code . '_' . time() . '.docx');

        $study = $request->studies->first();
        
        $viability = "Viável";
        if ($study?->viability_decision === 'not_viable') {
            $viability = "Inviável";
        } elseif ($study?->viability_decision === 'viable_with_restrictions') {
            $viability = "Viável com restrições";
        }

        $data = [
            'instructions' => [
                'Descrição da Necessidade' => $study?->need_description ?: $request->need_justification,
                '1. Descrição da necessidade da contratação' => $study?->need_description ?: $request->need_justification,
                'Motivação/Justificativa' => $study?->motivation ?: $request->need_justification,
                'Está prevista no Plano de Contratações Anual (PCA)?' => $study?->is_in_pca ? 'Sim' : 'Não',
                'Referência PCA:' => $study?->pca_reference ?: 'Não se aplica',
                'Descrição da demonstração:' => $study?->pca_description ?: ($study?->pca_demonstration ?: 'Não se aplica'),
                'Identificação da Área requisitante' => $request->requisition_unit ?? $request->secretaria,
                'Justificativa da Necessidade da Contratação' => $study?->need_description ?: $request->need_justification,
                'Providências Prévias ao Contrato' => $study?->prerequisites ?: 'Não se aplica.',
                '11. Providências Prévias ao Contrato' => $study?->prerequisites ?: 'Não se aplica.',
                'Contratações Correlatas e/ou Interdependentes' => $study?->correlated_contracts ?: ($study?->linked_contracts ?: 'Não foram identificadas contratações correlatas.'),
                'Requisitos Necessários à Solução' => $study?->solution_requirements ?: 'Não foram identificados requisitos específicos.',
                'Análise dos Possíveis Impactos Ambientais e Logística Reversa' => $study?->environmental_analysis ?: ($request->environmental_impacts ?: 'Não foram identificados impactos significativos.'),
                '12. Impactos Ambientais' => $study?->environmental_analysis ?: ($request->environmental_impacts ?: 'Não foram identificados impactos significativos.'),
                'Levantamento de Soluções' => $study?->solution_mapping ?: ($study?->solution_survey ?: 'A solução proposta foi baseada na necessidade direta da secretaria.'),
                'Registro de Soluções Consideradas Inviáveis' => $study?->discarded_solutions ?: ($study?->unviable_solutions ?: 'Não foram identificadas soluções inviáveis relevantes.'),
                'Justificativa para o Parcelamento ou Não da Contratação' => $study?->parceling_justification ?: ($study?->splitting_justification ?: 'O objeto não será parcelado visando a economia de escala.'),
                '8. Justificativa para o parcelamento' => $study?->parceling_justification ?: ($study?->splitting_justification ?: 'O objeto não será parcelado visando a economia de escala.'),
                'Descrição da Solução a ser Contratada' => $study?->chosen_solution ?: ($study?->solution_description ?: $request->object_summary),
                '7. Descrição da solução como um todo' => $study?->chosen_solution ?: ($study?->solution_description ?: $request->object_summary),
                'Estimativa de Custo Total da Contratação' => 'Conforme tabela de itens gerada abaixo.',
                '9. Estimativa do Valor da Contratação' => 'Conforme tabela de itens gerada abaixo.',
                'Demonstrativos dos Resultados Pretendidos' => $study?->expected_results ?: ($study?->intended_results ?: 'Atendimento imediato da demanda para continuidade do serviço público.'),
                '10. Resultados Pretendidos' => $study?->expected_results ?: ($study?->intended_results ?: 'Atendimento imediato da demanda para continuidade do serviço público.'),
                'Análise de Viabilidade da Contratação' => $study?->viability_analysis ?: "A contratação é considerada $viability.",
                '13. Declaração de Viabilidade' => $study?->viability_analysis ?: "A contratação é considerada $viability.",
                'Viabilidade' => $viability,
                'Justificativa' => $study?->viability_justification ?: 'A demanda atende aos requisitos técnicos e econômicos do órgão.',
                'Nome do responsável' => $request->requester_name ?? 'Não informado',
                'Estimativa da Demanda' => 'Abaixo segue a relação detalhada dos itens e quantidades estimadas:',
                'Programa de Compras – Levantamento de Soluções' => $study?->municipal_program_eligible ? "Item enquadrado no Programa Municipal de Compras para fomento local." : "Não se aplica.",
            ],
            'placeholders' => [
                'NOME:_autor' => ($request->requester_name ?? 'Não informado'),
                'CPF:_autor' => ($request->requester_cpf ?? ''),
                'CARGO/FUNÇÃO:_autor' => ($request->requester_role ?? ''),
                'NOME:_secretario' => ($request->responsible_name ?? 'Não informado'),
                'CPF:_secretario' => ($request->responsible_cpf ?? ''),
                'CARGO/FUNÇÃO:_secretario' => ($request->responsible_role ?? ''),
                
                '___SECRETARIA___' => config('compras.secretarias')[$request->secretaria] ?? $request->secretaria,
                '___ANO___' => date('Y'),
                '___DATA_HOJE___' => date('d/m/Y'),
                '___OBJETO_TITULO___' => $request->title,
                '{{declaracao_viabilidade}}' => "A contratação é considerada $viability.",
            ],
            'items' => $request->items->map(fn($item) => [
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit' => $item->unit,
                'unit_value' => $item->unit_value,
            ])->toArray(),
        ];

        return $this->callPythonGenerator($templatePath, $outputPath, $data);
    }

    /**
     * Executa o script Python para gerar o documento.
     */
    private function callPythonGenerator(string $templatePath, string $outputPath, array $data): string
    {
        if (!file_exists($templatePath)) {
            throw new \RuntimeException("Template não encontrado: " . $templatePath);
        }

        $scriptPath = base_path('app/Scripts/generate_document.py');
        $jsonData = json_encode($data);

        $pythonPath = env('PYTHON_PATH', 'python3');

        // Usa o Process do Laravel para execução segura
        $result = Process::run([
            $pythonPath,
            $scriptPath,
            '--template', $templatePath,
            '--output', $outputPath,
            '--data', $jsonData
        ]);

        Log::info('Python Generator Output', [
            'output' => $result->output(),
            'error' => $result->errorOutput()
        ]);

        if ($result->failed()) {
            Log::error('Erro ao gerar documento via Python', [
                'error' => $result->errorOutput(),
                'output' => $result->output(),
                'command' => $result->command()
            ]);
            throw new \RuntimeException("Erro ao gerar o documento: " . $result->errorOutput());
        }

        return $outputPath;
    }
}
