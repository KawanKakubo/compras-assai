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

        $secKey = $request->secretaria;
        $secretariaFullName = config("compras.secretarias.{$secKey}") ?? $secKey;

        $prioritiesMap = [
            'low' => 'Baixa',
            'medium' => 'Média',
            'high' => 'Alta',
        ];
        $prioridadeStr = $prioritiesMap[$request->priority_level] ?? 'Média';
        if ($request->priority_level === 'high' && !empty($request->priority_justification)) {
            $prioridadeStr .= ". Justificativa: " . $request->priority_justification;
        }

        $data = [
            'placeholders' => [
                '{{ data_prevista }}' => $request->planned_conclusion_at ? $request->planned_conclusion_at->format('d/m/Y') : 'Não prevista',
                '{{ descricao_objeto }}' => $request->object_summary ?: $request->title,
                '{{ grau_prioridade }}' => $prioridadeStr,
                '{{ justificativa_contratacao }}' => $request->need_justification,
                '{{ vinculacao_outro_contratacao }}' => $request->linked_request ?: 'Não há vinculação ou dependência com outro documento de formalização de demanda.',
                '{{ impactos_ambientais }}' => $request->environmental_impacts ?: 'Não foram identificados impactos ambientais significativos.',
                '{{ logistica_reversa }}' => $request->reverse_logistics ?: 'Não se aplica.',
                '{{ secretaria_requisitante }}' => $secretariaFullName,
                '{{ nome_autor }}' => $request->requester_name ?: 'Não informado',
                '{{ cpf_autor }}' => $request->requester_cpf ?: 'Não informado',
                '{{ funcao_autor }}' => $request->requester_role ?: 'Não informado',
                '{{ nome_secretario }}' => $request->responsible_name ?: 'Não informado',
                '{{ cpf_secretario }}' => $request->responsible_cpf ?: 'Não informado',
                '{{ funcao_secretario }}' => $request->responsible_role ?: 'Não informado',
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
        $request->loadMissing(['items', 'studies']);
        $templatePath = base_path('docs/MODELO_ETP.docx');
        $outputPath = storage_path('app/public/ETP_' . $request->reference_code . '_' . time() . '.docx');

        $study = $request->studies->first();
        $secKey = $request->secretaria;
        $secretariaFullName = config("compras.secretarias.{$secKey}") ?? $secKey;

        // PCA Field format
        $previstaPca = "Não.";
        if ($study && $study->is_in_pca) {
            $previstaPca = "Sim. Referência no PCA: " . ($study->pca_reference ?: 'Não informada') . " - Descrição do Item: " . ($study->pca_description ?: 'Não informada');
        }

        // Viability text
        $viabilityDecision = "Viável";
        if ($study?->viability_decision === 'viable_with_restrictions') {
            $viabilityDecision = "Viável com restrições";
        } elseif ($study?->viability_decision === 'not_viable') {
            $viabilityDecision = "Inviável";
        }
        $viabilidadeText = "{$viabilityDecision}. Justificativa: " . ($study?->viability_justification ?: 'A contratação atende plenamente aos requisitos de interesse público, oportunidade e conveniência do município.');

        // Fallbacks for ETP textareas
        $providencias = $study?->prerequisites ?: 'Para a execução da contratação, a Administração adotará as seguintes providências prévias: indicação formal do fiscal e gestor do contrato para acompanhamento e recebimento do objeto; conferência das especificações técnicas fornecidas pelo contratado; garantia de espaço físico/infraestrutura necessária para entrega e armazenamento dos itens; e alinhamento dos procedimentos de liquidação e pagamento junto à Secretaria de Finanças. Não há outras providências complexas ou atípicas pendentes.';
        
        $contratacoesCorrelatas = $study?->correlated_contracts ?: 'Não foram identificadas contratações correlatas ou interdependentes necessárias para a viabilidade ou complementação do objeto desta contratação.';
        
        $requisitosSolucao = $study?->solution_requirements ?: 'A solução deverá atender integralmente aos requisitos de qualidade, durabilidade, prazos de entrega e especificações detalhadas no termo de referência e catálogo CATMAT/CATSER. Os produtos/serviços fornecidos deverão estar em perfeita conformidade com as normas técnicas vigentes e padrões exigidos pela legislação.';
        
        $estimativaDemanda = $study?->demand_estimate ?: 'A estimativa da demanda foi realizada com base no levantamento histórico de consumo do órgão e no planejamento anual de necessidades da Secretaria Requisitante, buscando dimensionar de forma precisa e eficiente o quantitativo necessário para evitar desperdícios ou descontinuidade dos serviços públicos.';
        
        $impactosAmbientais = $study?->environmental_analysis ?: ($request->environmental_impacts ?: 'Os itens e serviços serão contratados observando os critérios de sustentabilidade ambiental, minimização de resíduos e eficiência energética aplicáveis. Não são previstos impactos ambientais negativos significativos. O fornecedor deverá observar as normas de descarte adequado e logística reversa quando aplicável à natureza do objeto.');
        
        $levantamentoSolucoes = $study?->solution_mapping ?: 'Foi realizado o levantamento de soluções no mercado, identificando-se que o fornecimento dos materiais/serviços descritos via catálogo CATMAT/CATSER representa a alternativa mais vantajosa, segura e padronizada para a Administração Pública, garantindo ampla competitividade e conformidade com a Lei 14.133/2021.';
        
        $parcelamento = $study?->parceling_justification ?: 'O objeto não será parcelado em lotes ou itens separados de forma a comprometer a economia de escala, a padronização e a eficiência administrativa do fornecimento. A contratação unificada é a mais adequada para assegurar a responsabilidade única pelo adimplemento da obrigação.';
        
        $descricaoSolucao = $study?->chosen_solution ?: $request->object_summary;
        
        $resultadosPretendidos = $study?->expected_results ?: 'Espera-se com esta contratação obter a melhoria contínua na prestação dos serviços públicos da Secretaria Requisitante, garantindo o suprimento tempestivo e regular das necessidades operacionais, com otimização dos recursos financeiros aplicados e total transparência.';

        $data = [
            'placeholders' => [
                '{{ descricao_necessidade }}' => $study?->need_description ?: $request->need_justification,
                '{{ motivacao_justificativa }}' => $study?->motivation ?: $request->need_justification,
                '{{ prevista_pca }}' => $previstaPca,
                '{{ secretaria_requisitante }}' => $secretariaFullName,
                '{{ justificativa_contratacao }}' => $request->need_justification,
                '{{ providencias_previas }}' => $providencias,
                '{{ contratacoes_correlatas }}' => $contratacoesCorrelatas,
                '{{ requisitos_solucao }}' => $requisitosSolucao,
                '{{ estimativa_demanda }}' => $estimativaDemanda,
                '{{ analise_possivel_impacto_ambiental }}' => $impactosAmbientais,
                '{{ levantamento_solucoes }}' => $levantamentoSolucoes,
                '{{ parcelamento_ou_nao }}' => $parcelamento,
                '{{ descricao_solucao }}' => $descricaoSolucao,
                '{{ demonstrativo_resultados }}' => $resultadosPretendidos,
                '{{ viabilidade }}' => $viabilidadeText,
                '{{ nome_autor }}' => $request->requester_name ?: 'Não informado',
                '{{ cpf_autor }}' => $request->requester_cpf ?: 'Não informado',
                '{{ funcao_autor }}' => $request->requester_role ?: 'Não informado',
                '{{ nome_secretario }}' => $request->responsible_name ?: 'Não informado',
                '{{ cpf_secretario }}' => $request->responsible_cpf ?: 'Não informado',
                '{{ funcao_secretario }}' => $request->responsible_role ?: 'Não informado',
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
