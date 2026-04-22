<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos - {{ $procurementRequest->reference_code }}</title>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="{{ asset('css/documents.css') }}">
</head>
<body class="document-view">

    <div class="action-bar no-print">
        <div>
            <h2 style="margin: 0; font-size: 1.2rem;">
                <i class="ph ph-files"></i> Documentos Gerados — {{ $procurementRequest->reference_code }}
            </h2>
            <span style="font-size: 0.85rem; color: #9ca3af;">{{ $legalFraming === 'licitacao' ? 'Licitação' : 'Dispensa de Licitação' }}</span>
        </div>
        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <a class="btn" style="background: #374151; text-decoration: none;" href="{{ route('planning.module-one.create') }}">
                <i class="ph ph-arrow-left"></i> Nova Demanda
            </a>
            <a class="btn" style="background: #2563eb; text-decoration: none;" href="{{ route('planning.module-one.download-sd', $procurementRequest) }}">
                <i class="ph ph-file-doc"></i> DOCX SD
            </a>
            <a class="btn" style="background: #2563eb; text-decoration: none;" href="{{ route('planning.module-one.download-etp', $procurementRequest) }}">
                <i class="ph ph-file-doc"></i> DOCX ETP
            </a>
            <a class="btn" style="background: #10b981; text-decoration: none;" href="{{ route('planning.module-one.download-tr', $procurementRequest) }}">
                <i class="ph ph-file-doc"></i> DOCX Termo de Ref.
            </a>
            <button class="btn" onclick="window.print()">
                <i class="ph ph-printer"></i> Imprimir / PDF
            </button>
        </div>
    </div>

    <div class="document-container">

        <!-- =========================================================
             DOCUMENTO 1: SOLICITAÇÃO DE DEMANDA (SD / DFD)
             ========================================================= -->
        <div class="document-sheet" id="doc-sd">
            <div class="doc-header">
                <!-- <img src="{{ asset('img/brasao.png') }}" alt="Brasão"> -->
                <div class="gov-name">Município de Assaí - Estado do Paraná</div>
                <div class="secretaria-name">{{ $secretarias[$procurementRequest->secretaria] ?? $procurementRequest->requisition_unit }}</div>
            </div>

            <h1>DOCUMENTO DE FORMALIZAÇÃO DE DEMANDA - DFD / SD</h1>
            
            <p><strong>Número da Solicitação:</strong> {{ $procurementRequest->reference_code }}</p>
            <p><strong>Data:</strong> {{ $procurementRequest->created_at->format('d/m/Y') }}</p>

            <h2>1. JUSTIFICATIVA DA NECESSIDADE DA CONTRATAÇÃO</h2>
            <p>A presente contratação faz-se necessária pelos seguintes motivos: {{ $procurementRequest->need_justification }}</p>

            <h2>2. DESCRIÇÃO SUCINTA DO OBJETO</h2>
            <p>{{ $procurementRequest->title }}. {{ $procurementRequest->object_summary }}</p>

            <h2>3. ESTIMATIVA DE QUANTIDADES E PREÇOS</h2>
            <p>Abaixo apresentamos a relação de itens, quantidades e a pesquisa prévia de preços de mercado baseada no Painel de Preços (Compras.gov.br):</p>
            
            <table class="official-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>CATMAT/CATSER</th>
                        <th>Descrição</th>
                        <th>Und</th>
                        <th>Qtd</th>
                        <th>V. Unit (R$)</th>
                        <th>V. Total (R$)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $index => $item)
                        <tr>
                            <td class="num">{{ $index + 1 }}</td>
                            <td>{{ $item->catalog_code }}</td>
                            <td>{{ $item->description }}</td>
                            <td>{{ $item->unit }}</td>
                            <td class="num">{{ number_format($item->quantity, 4, ',', '.') }}</td>
                            <td class="num">{{ number_format($item->unit_value, 2, ',', '.') }}</td>
                            <td class="num">{{ number_format($item->total_value, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="6" style="text-align: right;">VALOR TOTAL ESTIMADO</td>
                        <td class="num">{{ number_format($totalEstimated, 2, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>

            <h2>4. PRIORIDADE</h2>
            <p>O grau de prioridade desta contratação é: <strong>{{ $prioridades[$procurementRequest->priority_level] ?? 'Média' }}</strong>.</p>
            @if($procurementRequest->priority_level === 'high')
                <p><strong>Justificativa da Urgência:</strong> {{ $procurementRequest->priority_justification }}</p>
            @endif

            <h2>5. SUSTENTABILIDADE E IMPACTOS</h2>
            <p>
                <strong>Impactos Ambientais:</strong> 
                {{ $procurementRequest->has_environmental_impact ? 'Sim. ' . $procurementRequest->environmental_impacts : 'Não há impactos ambientais significativos previstos.' }}
            </p>
            <p>
                <strong>Logística Reversa:</strong>
                {{ $procurementRequest->has_reverse_logistics ? 'Sim. ' . $procurementRequest->reverse_logistics : 'Não se aplica logística reversa para este objeto.' }}
            </p>

            <div class="signatures">
                <div class="signature-block">
                    <div class="signature-line"></div>
                    <div class="name">{{ $procurementRequest->requester_name ?? 'REQUISITANTE' }}</div>
                    <div class="role">{{ $procurementRequest->requester_role ?? 'Cargo/Função' }}</div>
                    <div class="cpf">CPF: {{ $procurementRequest->requester_cpf ?? '___.___.___-__' }}</div>
                </div>
                <div class="signature-block">
                    <div class="signature-line"></div>
                    <div class="name">{{ $procurementRequest->responsible_name ?? 'AUTORIDADE COMPETENTE' }}</div>
                    <div class="role">{{ $procurementRequest->responsible_role ?? 'Secretário(a) Municipal' }}</div>
                    <div class="cpf">CPF: {{ $procurementRequest->responsible_cpf ?? '___.___.___-__' }}</div>
                </div>
            </div>
        </div>

        <!-- =========================================================
             DOCUMENTO 2: ESTUDO TÉCNICO PRELIMINAR (ETP)
             ========================================================= -->
        @if($study)
        <div class="document-sheet" id="doc-etp">
            <div class="doc-header">
                <!-- <img src="{{ asset('img/brasao.png') }}" alt="Brasão"> -->
                <div class="gov-name">Município de Assaí - Estado do Paraná</div>
                <div class="secretaria-name">{{ $secretarias[$procurementRequest->secretaria] ?? $procurementRequest->requisition_unit }}</div>
            </div>

            <h1>ESTUDO TÉCNICO PRELIMINAR - ETP</h1>
            <p style="text-align: center; margin-bottom: 24px;">Lei nº 14.133/2021, Art. 18, § 1º</p>

            <h2>1. INFORMAÇÕES BÁSICAS</h2>
            <p><strong>Referência SD:</strong> {{ $procurementRequest->reference_code }}</p>
            <p><strong>Objeto:</strong> {{ $procurementRequest->title }}</p>
            <p><strong>Alinhamento ao PCA:</strong> {{ $study->is_in_pca ? 'Sim. ' . $study->pca_reference : 'A demanda não consta no Plano de Contratações Anual vigente e requer inclusão.' }}</p>

            <h2>2. DESCRIÇÃO DA NECESSIDADE DA CONTRATAÇÃO</h2>
            <p>{{ $study->need_description }}</p>

            <h2>3. REQUISITOS DA CONTRATAÇÃO E RESULTADOS PRETENDIDOS</h2>
            <p><strong>Requisitos Técnicos:</strong> {{ $study->solution_requirements ?? 'Não há requisitos técnicos específicos além das descrições dos itens.' }}</p>
            <p><strong>Resultados Pretendidos:</strong> {{ $study->expected_results ?? 'Garantir o pleno funcionamento do órgão requisitante.' }}</p>

            <h2>4. LEVANTAMENTO DE MERCADO E JUSTIFICATIVA DE PARCELAMENTO</h2>
            <p><strong>Alternativas Analisadas:</strong> {{ $study->solution_mapping }}</p>
            @if($study->discarded_solutions)
                <p><strong>Soluções Descartadas:</strong> {{ $study->discarded_solutions }}</p>
            @endif
            <p><strong>Justificativa sobre o Parcelamento:</strong> {{ $study->parceling_justification }}</p>

            <h2>5. PROGRAMA MUNICIPAL DE COMPRAS</h2>
            <p>Conforme o <strong>{{ $programaMunicipal['programa'] }}</strong>, a presente contratação foi analisada quanto ao fomento do comércio local:</p>
            <p><strong>Enquadramento:</strong> {{ $study->municipal_program_eligible ? 'Objeto elegível. ' . $study->municipal_program_segment : 'Não aplicável ou não elegível para fomento local.' }}</p>
            @if($study->municipal_program_eligible)
                <p><strong>Recomendação Aplicada:</strong> {{ $study->municipal_program_recommendation }}</p>
                <p><strong>Justificativa:</strong> {{ $study->municipal_program_justification }}</p>
            @endif

            <h2>6. DECLARAÇÃO DE VIABILIDADE</h2>
            <p>Com base nas análises acima, declaramos que a presente contratação é: 
                <strong>
                @if($study->viability_decision === 'viable')
                    VIÁVEL
                @elseif($study->viability_decision === 'viable_with_restrictions')
                    VIÁVEL COM RESTRIÇÕES
                @else
                    INVIÁVEL
                @endif
                </strong>.
            </p>
            
            <ul>
                <li><strong>Viabilidade Técnica:</strong> {{ $study->viability_technical ?? 'Atestada.' }}</li>
                <li><strong>Viabilidade Econômica:</strong> {{ $study->viability_economic ?? 'Atestada via pesquisa de preços do PNCP.' }}</li>
            </ul>

            <div class="signatures">
                @if($study->team_signatures && count($study->team_signatures) > 0)
                    @foreach($study->team_signatures as $member)
                        <div class="signature-block">
                            <div class="signature-line"></div>
                            <div class="name">{{ $member['name'] }}</div>
                            <div class="role">{{ $member['role'] ?? 'Membro da Equipe' }}</div>
                        </div>
                    @endforeach
                @else
                    <div class="signature-block">
                        <div class="signature-line"></div>
                        <div class="name">EQUIPE DE PLANEJAMENTO</div>
                        <div class="role">Conforme {{ $study->planning_team_portaria ?? 'Portaria de Designação' }}</div>
                    </div>
                @endif
            </div>
        </div>
        @endif

        <!-- =========================================================
             DOCUMENTO 3: TERMO DE REFERÊNCIA (TR)
             ========================================================= -->
        <div class="document-sheet" id="doc-tr">
            <div class="doc-header">
                <div class="gov-name">Município de Assaí - Estado do Paraná</div>
                <div class="secretaria-name">{{ $secretarias[$procurementRequest->secretaria] ?? $procurementRequest->requisition_unit }}</div>
            </div>

            <h1>TERMO DE REFERÊNCIA - TR</h1>
            
            <h2>1. OBJETO E FUNDAMENTAÇÃO LEGAL</h2>
            <p><strong>1.1.</strong> O presente Termo de Referência tem por objeto a <strong>{{ $procurementRequest->title }}</strong>, conforme condições, quantidades e exigências aqui estabelecidas.</p>
            <p><strong>1.2.</strong> A contratação decorre da necessidade fundamentada no Estudo Técnico Preliminar (ETP) apenso aos autos (SD {{ $procurementRequest->reference_code }}).</p>
            <p><strong>1.3.</strong> Enquadramento Legal Sugerido: 
                <strong>
                    @if($legalFraming === 'dispensa_servico')
                        Dispensa de Licitação - Art. 75, Inciso II da Lei 14.133/2021 (Serviços e Compras em Geral)
                    @elseif($legalFraming === 'dispensa_material')
                        Dispensa de Licitação - Art. 75, Inciso I da Lei 14.133/2021 (Obras e Serviços de Engenharia/Manutenção)
                    @else
                        Licitação (Pregão / Concorrência) - Lei 14.133/2021
                    @endif
                </strong>
            </p>

            <h2>2. ESPECIFICAÇÕES TÉCNICAS E QUANTITATIVOS</h2>
            <p><strong>2.1.</strong> Os bens/serviços objeto desta contratação devem obedecer rigorosamente às especificações do catálogo CATMAT/CATSER e às descrições detalhadas abaixo:</p>
            
            <table class="official-table">
                <thead>
                    <tr>
                        <th width="5%">Item</th>
                        <th width="15%">CATMAT/SER</th>
                        <th width="50%">Especificação Detalhada</th>
                        <th width="10%">Unidade</th>
                        <th width="10%">Quant.</th>
                        <th width="10%">Valor Est. (R$)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $index => $item)
                        <tr>
                            <td class="num">{{ $index + 1 }}</td>
                            <td>{{ $item->catalog_code }}</td>
                            <td>
                                <strong>{{ $item->description }}</strong>
                                @if($item->notes)
                                    <br><small>Obs: {{ $item->notes }}</small>
                                @endif
                            </td>
                            <td>{{ $item->unit }}</td>
                            <td class="num">{{ number_format($item->quantity, 4, ',', '.') }}</td>
                            <td class="num">{{ number_format($item->unit_value, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <h2>3. REQUISITOS E CRITÉRIOS DE ACEITAÇÃO</h2>
            <p><strong>3.1.</strong> {{ $study->solution_requirements ?? 'Os itens deverão ser entregues/executados em perfeitas condições, sendo recusados aqueles que apresentarem falhas, defeitos ou divergências em relação às especificações.' }}</p>

            <h2>4. EXECUÇÃO, ENTREGA E PAGAMENTO</h2>
            <p><strong>4.1.</strong> O prazo de entrega/execução será de até 30 (trinta) dias contados do recebimento da Nota de Empenho/Ordem de Serviço.</p>
            <p><strong>4.2.</strong> O pagamento será realizado em até 30 (trinta) dias após a atestação da Nota Fiscal pelo setor competente.</p>

            <div class="signatures" style="margin-top: 100px;">
                <div class="signature-block">
                    <div class="signature-line"></div>
                    <div class="name">{{ $procurementRequest->requester_name ?? 'ELABORADOR DO TR' }}</div>
                    <div class="role">{{ $procurementRequest->requester_role ?? 'Setor Requisitante' }}</div>
                </div>
                <div class="signature-block">
                    <div class="signature-line"></div>
                    <div class="name">{{ $procurementRequest->responsible_name ?? 'AUTORIDADE COMPETENTE' }}</div>
                    <div class="role">Aprovação do TR (Art. 14, § 1º)</div>
                </div>
            </div>
        </div>

    </div>
</body>
</html>