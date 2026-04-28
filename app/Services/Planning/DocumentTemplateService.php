<?php

namespace App\Services\Planning;

use App\Models\Planning\ProcurementRequest;
use Illuminate\Support\Str;
use ZipArchive;

class DocumentTemplateService
{
    /**
     * Gera o Documento de Formalização da Demanda (DFD/SD)
     */
    public function generateSd(ProcurementRequest $request): string
    {
        $request->loadMissing(['items', 'studies', 'user']);
        $templatePath = base_path('docs/MODELO_SD.docx');
        $outputPath = storage_path('app/public/DFD_' . $request->reference_code . '_' . time() . '.docx');

        return $this->processTemplate($templatePath, $outputPath, function ($xml) use ($request) {
            return $this->prepareSdXml($xml, $request);
        });
    }

    /**
     * Gera o Estudo Técnico Preliminar (ETP)
     */
    public function generateEtp(ProcurementRequest $request): string
    {
        $request->loadMissing(['items', 'studies', 'user']);
        $templatePath = base_path('docs/MODELO_ETP.docx');
        $outputPath = storage_path('app/public/ETP_' . $request->reference_code . '_' . time() . '.docx');

        return $this->processTemplate($templatePath, $outputPath, function ($xml) use ($request) {
            return $this->prepareEtpXml($xml, $request);
        });
    }

    /**
     * Processa um template DOCX extraindo o XML, aplicando as mudanças e salvando.
     */
    private function processTemplate(string $templatePath, string $outputPath, callable $processor): string
    {
        if (!file_exists($templatePath)) {
            throw new \RuntimeException("Template não encontrado: " . $templatePath);
        }

        copy($templatePath, $outputPath);

        $zip = new ZipArchive();
        if ($zip->open($outputPath) === true) {
            $xmlContent = $zip->getFromName('word/document.xml');
            $newXml = $processor($xmlContent);
            
            // Final cleanup of any remaining instructions before saving
            $newXml = $this->cleanupTemplateMarkup($newXml);
            
            $zip->addFromString('word/document.xml', $newXml);
            $zip->close();
        } else {
            throw new \RuntimeException("Não foi possível abrir o arquivo temporário: " . $outputPath);
        }

        return $outputPath;
    }

    private function prepareSdXml(string $xml, ProcurementRequest $request): string
    {
        $secretarias = config('compras.secretarias');
        $secName = $secretarias[$request->secretaria] ?? $request->secretaria;

        // Variáveis Mapeadas no DFD
        $data = [
            '{{data_previsao}}' => $request->planned_conclusion_at ? $request->planned_conclusion_at->format('d/m/Y') : date('d/m/Y'),
            '{{descricao_objeto}}' => $request->title,
            '{{prioridade}}' => config('compras.prioridades')[$request->priority_level] ?? $request->priority_level,
            '{{justificativa}}' => $request->need_justification,
            '{{assinatura_autor}}' => ($request->requester_name ?? 'Não informado') . "\nCPF: " . ($request->requester_cpf ?? '') . "\nCargo: " . ($request->requester_role ?? ''),
            '{{assinatura_secretario}}' => ($request->responsible_name ?? 'Não informado') . "\nCPF: " . ($request->responsible_cpf ?? '') . "\nCargo: " . ($request->responsible_role ?? ''),
            
            // Suporte para placeholders antigos/alternativos
            '___SECRETARIA___' => $secName,
            '___ANO___' => date('Y'),
            '___CODIGO_REF___' => $request->reference_code,
            '___DATA_HOJE___' => date('d/m/Y'),
            '___OBJETO_TITULO___' => $request->title,
            '___RESUMO_OBJETO___' => $request->object_summary,
        ];

        $xml = $this->replacePlaceholders($xml, $data);

        // Tabela de itens com cálculo automático
        $xml = $this->replaceTablePlaceholder($xml, '{{tabela_itens}}', $request->items);
        $xml = $this->replaceTablePlaceholder($xml, '___LISTA_ITENS___', $request->items);

        return $xml;
    }

    private function prepareEtpXml(string $xml, ProcurementRequest $request): string
    {
        $study = $request->studies->first();
        
        $viability = "A contratação é considerada VIÁVEL.";
        if ($study?->viability_decision === 'not_viable') {
            $viability = "A contratação é considerada INVIÁVEL. " . ($study->viability_justification ?: '');
        } elseif ($study?->viability_decision === 'viable_with_restrictions') {
            $viability = "A contratação é considerada VIÁVEL COM RESTRIÇÕES. " . ($study->viability_justification ?: '');
        }

        // Variáveis Mapeadas no ETP
        $data = [
            '{{descricao_necessidade}}' => $study?->need_description ?: $request->need_justification,
            '{{previsao_pca}}' => $study?->is_in_pca ? 'Sim. ' . $study->pca_reference : 'Não consta no PCA vigente.',
            '{{providencias_previas}}' => $study?->prerequisites ?: 'Não foram identificadas providências prévias necessárias.',
            '{{declaracao_viabilidade}}' => $viability,
            
            // Suporte para placeholders de instrução
            '___SECRETARIA___' => config('compras.secretarias')[$request->secretaria] ?? $request->secretaria,
            '___ANO___' => date('Y'),
            '___DATA_HOJE___' => date('d/m/Y'),
            '___OBJETO_TITULO___' => $request->title,
        ];

        $xml = $this->replacePlaceholders($xml, $data);

        // Tabela de estimativa de custo
        $xml = $this->replaceTablePlaceholder($xml, '{{estimativa_custo_tabela}}', $request->items);

        // Mapeamento de instruções dinâmicas do modelo v6
        $instructionMap = [
            'necessidade que motivou' => $study?->need_description ?: $request->need_justification,
            'motivos e justificativas' => $study?->motivation ?: $request->need_justification,
            'objetivo do estudo técnico' => 'Identificar a solução mais vantajosa para a Administração Pública, garantindo a transparência e legalidade.',
            'funções, funcionalidades' => $study?->solution_requirements ?: 'Conforme especificações técnicas.',
            'requisitos da solução' => $study?->solution_requirements ?: 'Conforme especificações técnicas.',
            'estimativa das quantidades' => $study?->demand_estimate ?: 'Baseada no consumo histórico.',
            'memória de cálculo' => $request->demand_memory_calculation ?: 'Calculado com base na demanda histórica.',
            'levantamento de mercado' => $study?->solution_mapping ?: 'Identificado soluções padrão de mercado.',
            'alternativas e justificativa' => $study?->discarded_solutions ?: 'Não foram identificadas soluções alternativas superiores.',
            'estimativa do valor' => $study?->estimated_total_cost ? 'R$ ' . number_format($study->estimated_total_cost, 2, ',', '.') : 'R$ ' . number_format($request->items->sum('total_value'), 2, ',', '.'),
            'descrição da solução como um todo' => $study?->chosen_solution ?: $request->title,
            'justificativa para o parcelamento' => $study?->parceling_justification ?: 'Parcelamento conforme conveniência administrativa.',
            'resultados pretendidos' => $study?->expected_results ?: 'Atendimento das necessidades operacionais.',
            'providências prévias' => $study?->prerequisites ?: 'Não se aplica.',
            'contratações correlatas' => $study?->correlated_contracts ?: 'Não se aplica.',
            'impactos ambientais' => $study?->environmental_analysis ?: ($request->has_environmental_impact ? $request->environmental_impacts : 'Não foram identificados impactos significativos.'),
            'viabilidade da contratação' => $viability,
        ];

        $xml = $this->replaceInstructionParagraphs($xml, $instructionMap);

        return $xml;
    }

    /**
     * Substitui um placeholder por uma tabela XML formatada.
     * Tenta encontrar o parágrafo que contém o placeholder para substituí-lo inteiramente pela tabela,
     * garantindo que a estrutura do XML permaneça válida.
     */
    private function replaceTablePlaceholder(string $xml, string $placeholder, $items): string
    {
        if (!str_contains($xml, $placeholder)) return $xml;

        $tableXml = '<w:tbl>
            <w:tblPr>
                <w:tblW w:w="5000" w:type="pct"/>
                <w:tblBorders>
                    <w:top w:val="single" w:sz="4" w:space="0" w:color="000000"/>
                    <w:left w:val="single" w:sz="4" w:space="0" w:color="000000"/>
                    <w:bottom w:val="single" w:sz="4" w:space="0" w:color="000000"/>
                    <w:right w:val="single" w:sz="4" w:space="0" w:color="000000"/>
                    <w:insideH w:val="single" w:sz="4" w:space="0" w:color="000000"/>
                    <w:insideV w:val="single" w:sz="4" w:space="0" w:color="000000"/>
                </w:tblBorders>
            </w:tblPr>
            <w:tr>
                <w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/></w:rPr><w:t>Item</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/></w:rPr><w:t>Descrição</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/></w:rPr><w:t>Qtd</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/></w:rPr><w:t>Unid</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/></w:rPr><w:t>V. Unit</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/></w:rPr><w:t>V. Total</w:t></w:r></w:p></w:tc>
            </w:tr>';

        $totalGeral = 0;
        foreach ($items as $idx => $item) {
            $totalItem = $item->quantity * $item->unit_value;
            $totalGeral += $totalItem;
            
            $tableXml .= '<w:tr>
                <w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:t>' . ($idx + 1) . '</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>' . $this->escape($item->description) . '</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:t>' . number_format($item->quantity, 2, ',', '.') . '</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:t>' . $this->escape($item->unit) . '</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:pPr><w:jc w:val="right"/></w:pPr><w:r><w:t>R$ ' . number_format($item->unit_value, 2, ',', '.') . '</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:pPr><w:jc w:val="right"/></w:pPr><w:r><w:t>R$ ' . number_format($totalItem, 2, ',', '.') . '</w:t></w:r></w:p></w:tc>
            </w:tr>';
        }

        $tableXml .= '<w:tr>
            <w:tc><w:tcPr><w:gridSpan w:val="5"/></w:tcPr><w:p><w:pPr><w:jc w:val="right"/></w:pPr><w:r><w:rPr><w:b/></w:rPr><w:t>TOTAL GERAL:</w:t></w:r></w:p></w:tc>
            <w:tc><w:p><w:pPr><w:jc w:val="right"/></w:pPr><w:r><w:rPr><w:b/></w:rPr><w:t>R$ ' . number_format($totalGeral, 2, ',', '.') . '</w:t></w:r></w:p></w:tc>
        </w:tr>';

        $tableXml .= '</w:tbl>';

        // Tenta encontrar o parágrafo <w:p> que contém o placeholder e substitui o parágrafo todo pela tabela
        $pattern = '/<w:p(?:\s+[^>]*)?>(?:(?!<w:p>).)*?' . preg_quote($placeholder, '/') . '.*?<\/w:p>/s';
        
        if (preg_match($pattern, $xml)) {
            return preg_replace($pattern, $tableXml, $xml);
        }

        // Fallback: substituição simples (pode quebrar XML se estiver dentro de <w:t>)
        return str_replace($placeholder, $tableXml, $xml);
    }

    /**
     * Substitui placeholders lidando com a fragmentação de tags do Word.
     */
    private function replacePlaceholders(string $xml, array $data): string
    {
        foreach ($data as $placeholder => $value) {
            // Se o placeholder literal existir, substitui
            if (str_contains($xml, $placeholder)) {
                $xml = str_replace($placeholder, $this->escape($value), $xml);
                continue;
            }

            // Caso contrário, tenta uma regex que ignora tags XML entre os caracteres do placeholder
            $chars = str_split($placeholder);
            $regex = '';
            foreach ($chars as $char) {
                $regex .= preg_quote($char, '/') . '(?:<[^>]+>)*';
            }
            
            $xml = preg_replace_callback('/' . $regex . '/s', function($matches) use ($value) {
                return $this->escape($value);
            }, $xml);
        }
        return $xml;
    }

    /**
     * Substitui textos entre < e > lidando com tags XML que podem quebrar a string.
     */
    private function replaceInstructionParagraphs(string $xml, array $instructionMap): string
    {
        return preg_replace_callback('/<w:p(?:\s+[^>]*)?>(?:(?!<w:p>).)*?&lt;.*?&gt;.*?<\/w:p>/s', function ($matches) use ($instructionMap) {
            $paragraph = $matches[0];
            
            // Extrai o texto limpo para comparação
            $cleanText = mb_strtolower(strip_tags(html_entity_decode($paragraph)));
            
            foreach ($instructionMap as $needle => $replacement) {
                if (str_contains($cleanText, mb_strtolower($needle))) {
                    // Mantém as propriedades do parágrafo (estilo, numeração)
                    preg_match('/<w:pPr>.*?<\/w:pPr>/s', $paragraph, $pPrMatch);
                    $pPr = $pPrMatch[0] ?? '';
                    return "<w:p>{$pPr}<w:r><w:t>" . $this->escape($replacement) . "</w:t></w:r></w:p>";
                }
            }

            // Se for uma instrução clara (começa com < e termina com >) e não mapeada, removemos
            if (preg_match('/^&lt;[^&]+&gt;$/', trim(strip_tags($paragraph)))) {
                return ''; 
            }

            // Caso contrário, apenas removemos as marcações de instrução mantendo o texto original se houver
            return preg_replace('/&lt;.*?&gt;/s', '', $paragraph);
        }, $xml);
    }

    /**
     * Remove marcações de template e limpa o XML final.
     */
    private function cleanupTemplateMarkup(string $xml): string
    {
        // Remove parágrafos que sobraram e são EXCLUSIVAMENTE instruções não preenchidas
        $xml = preg_replace_callback('/<w:p(?:\s+[^>]*)?>(?:(?!<w:p>).)*?<\/w:p>/s', function($matches) {
            $p = $matches[0];
            $text = trim(strip_tags($p));
            // Se o parágrafo só contém marcação de instrução <...> ou [....], remove
            if (preg_match('/^(&lt;|\[).*?(&gt;|\])$/s', $text)) {
                return '';
            }
            return $p;
        }, $xml);

        // Remove runs (w:r) que tenham cor vermelha (instruções visuais)
        $xml = preg_replace('/<w:r(?:\s+[^>]*)?>.*?<w:color w:val="(?:FF|EE)0000"[^>]*\/>.*?<\/w:r>/si', '', $xml);

        // Remove tags de erro de revisão do Word
        $xml = preg_replace('/<w:proofErr[^>]*\/>/i', '', $xml);
        
        // Remove qualquer marcador remanescente de instrução < ... > individual
        $xml = preg_replace('/&lt;.*?&gt;/s', '', $xml);
        
        // Remove espaços duplos
        $xml = preg_replace('/\s{2,}/', ' ', $xml);

        return $xml;
    }

    private function escape($text): string
    {
        if ($text === null) return '';
        $escaped = htmlspecialchars((string)$text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        return str_replace(["\r\n", "\r", "\n"], '</w:t><w:br/><w:t>', $escaped);
    }
}
