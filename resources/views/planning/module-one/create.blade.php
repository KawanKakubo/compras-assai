<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compras Assaí — Inteligência em Compras</title>
    <!-- Phosphor Icons para iconografia moderna -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="{{ asset('css/wizard.css') }}">
</head>
<body>

<div class="wizard-container">
    <div class="wizard-header">
        <div style="margin-bottom: 1rem; opacity: 0.8;">
            <a href="{{ route('login') }}" style="color: var(--text-muted); text-decoration: none; font-size: 0.8rem; display: flex; align-items: center; gap: 4px; justify-content: center;">
                <i class="ph ph-arrow-left"></i> Voltar ao Dashboard
            </a>
        </div>
        <h1>Compras Assaí</h1>
        <p>Assistente Inteligente de Planejamento da Contratação</p>
    </div>

    <!-- Barra de Progresso -->
    <div class="progress-bar">
        <div class="progress-step active"><div class="step-dot">1</div><div class="step-label">Identificação</div></div>
        <div class="progress-step"><div class="step-dot">2</div><div class="step-label">Necessidade</div></div>
        <div class="progress-step"><div class="step-dot">3</div><div class="step-label">Itens (CATMAT)</div></div>
        <div class="progress-step"><div class="step-dot">4</div><div class="step-label">Preços & Enquadramento</div></div>
        <div class="progress-step"><div class="step-dot">5</div><div class="step-label">Análises do ETP</div></div>
        <div class="progress-step"><div class="step-dot">6</div><div class="step-label">Viabilidade</div></div>
        <div class="progress-step"><div class="step-dot">7</div><div class="step-label">Revisão</div></div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger" style="margin-bottom: 24px;">
            <i class="ph ph-warning-circle" style="font-size: 1.5rem"></i>
            <div>
                <strong>Existem erros de validação:</strong>
                <ul style="margin-top: 8px; padding-left: 20px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form id="wizard-form" action="{{ route('planning.module-one.store') }}" method="POST">
        @csrf

        <!-- =========================================================
             STEP 1: IDENTIFICAÇÃO
             ========================================================= -->
        <div class="wizard-step active" id="step-1">
            <h2 class="step-title">Identificação da Demanda</h2>
            <p class="step-subtitle">Informações básicas de quem está pedindo.</p>

            <div class="card">
                <div class="card-title"><i class="ph ph-buildings"></i> Dados da Secretaria</div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Secretaria Requisitante *</label>
                        @if(auth()->check() && auth()->user()->isSecretaria() && auth()->user()->secretaria_name)
                            <input type="text" value="{{ auth()->user()->secretaria_name }}" readonly style="background: rgba(255,255,255,0.05); color: var(--text-muted); cursor: not-allowed; border-color: transparent;">
                            <input type="hidden" name="secretaria" value="{{ auth()->user()->secretaria_name }}" data-name="{{ auth()->user()->secretaria_name }}">
                        @else
                            <select name="secretaria" required>
                                <option value="">Selecione a secretaria...</option>
                                @foreach($secretarias as $key => $name)
                                    <option value="{{ $key }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                    <div class="form-group">
                        <label>Grau de Prioridade *</label>
                        <select name="priority_level" id="priority_level" required>
                            <option value="low">Baixa (Rotina)</option>
                            <option value="medium" selected>Média (Planejada)</option>
                            <option value="high">Alta (Urgência)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group conditional" id="conditional-priority">
                    <label>Justificativa para Prioridade Alta *</label>
                    <textarea name="priority_justification" placeholder="Por que essa demanda é urgente? Quais os riscos se não for atendida agora?"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Previsão Desejada para Conclusão</label>
                        <input type="date" name="planned_conclusion_at">
                    </div>
                    <div class="form-group">
                        <label>Código de Referência Gerado</label>
                        <input type="text" name="reference_code" value="{{ $nextReferenceCode }}" readonly style="background: rgba(0,0,0,0.2); color: var(--text-muted); cursor: not-allowed;">
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-title"><i class="ph ph-user"></i> Servidores Responsáveis</div>
                
                <div style="margin-bottom: 2rem;">
                    <h4 style="margin-bottom: 1rem; color: var(--accent); font-size: 0.9rem; text-transform: uppercase;">1. Requisitante (Quem está pedindo)</h4>
                    <div class="form-row-3">
                        <div class="form-group">
                            <label>Nome do Requisitante *</label>
                            <input type="text" name="requester_name" value="{{ auth()->user()->name ?? '' }}" required placeholder="Nome completo">
                        </div>
                        <div class="form-group">
                            <label>Cargo/Função *</label>
                            <input type="text" name="requester_role" required placeholder="Ex: Diretor de Saúde">
                        </div>
                        <div class="form-group">
                            <label>CPF *</label>
                            <input type="text" name="requester_cpf" class="mask-cpf" required placeholder="000.000.000-00">
                        </div>
                    </div>
                </div>

                <hr style="border: 0; border-top: 1px solid var(--border); margin: 2rem 0;">

                <div>
                    <h4 style="margin-bottom: 1rem; color: var(--accent); font-size: 0.9rem; text-transform: uppercase;">2. Autoridade Responsável (Quem assina)</h4>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1rem;">Geralmente o Secretário da pasta ou Diretor Geral.</p>
                    <div class="form-row-3">
                        <div class="form-group">
                            <label>Nome da Autoridade *</label>
                            <input type="text" name="responsible_name" required placeholder="Nome completo">
                        </div>
                        <div class="form-group">
                            <label>Cargo/Função *</label>
                            <input type="text" name="responsible_role" required placeholder="Ex: Secretário Municipal">
                        </div>
                        <div class="form-group">
                            <label>CPF *</label>
                            <input type="text" name="responsible_cpf" class="mask-cpf" required placeholder="000.000.000-00">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- =========================================================
             STEP 2: NECESSIDADE
             ========================================================= -->
        <div class="wizard-step" id="step-2">
            <h2 class="step-title">A Necessidade</h2>
            <p class="step-subtitle">Explique com palavras simples o que você precisa contratar e por quê.</p>

            <div class="card">
                <div class="card-title"><i class="ph ph-target"></i> Objeto</div>
                <div class="form-group">
                    <label>Título da Demanda *</label>
                    <div class="hint">Ex: Aquisição de material de expediente para as escolas municipais.</div>
                    <input type="text" name="title" id="title" required placeholder="O que você está comprando?">
                </div>

                <div class="form-group">
                    <label>Resumo do Objeto (Máx 200 caracteres) * <span class="legal-ref">DFD Art. 8º, II</span></label>
                    <textarea name="object_summary" maxlength="200" required rows="2" placeholder="Resumo curto para publicação..."></textarea>
                </div>
            </div>

            <div class="card">
                <div class="card-title"><i class="ph ph-chat-text"></i> Justificativa</div>
                <div class="form-group">
                    <label>Por que você precisa disso? * <span class="legal-ref">DFD Art. 8º, I</span></label>
                    <div class="hint">Explique o problema que esta contratação vai resolver para o município.</div>
                    <textarea name="need_justification" required rows="4" placeholder="Descreva o contexto, o problema e a solução..."></textarea>
                </div>
                <div class="form-group">
                    <label>Vínculo com outra contratação? <span class="legal-ref">DFD Art. 8º, VII</span></label>
                    <div class="hint">Se esta demanda depende de outra para funcionar, informe aqui.</div>
                    <input type="text" name="linked_request" placeholder="Ex: SD-2026-001 ou 'Não se aplica'">
                </div>
            </div>

            <div class="card">
                <div class="card-title"><i class="ph ph-leaf"></i> Impactos e Sustentabilidade</div>
                
                <div class="form-group">
                    <label style="display:inline-block; margin-right: 16px;">Gera Impacto Ambiental?</label>
                    <div class="toggle-group" style="display:inline-flex;">
                        <label><input type="radio" name="has_environmental_impact" value="1"> Sim</label>
                        <label style="margin-left: 10px;"><input type="radio" name="has_environmental_impact" value="0" checked> Não</label>
                    </div>
                </div>
                
                <div class="form-group conditional" id="conditional-environmental">
                    <textarea name="environmental_impacts" placeholder="Descreva os impactos e as medidas de mitigação exigidas no TR..."></textarea>
                </div>

                <div class="form-group">
                    <label style="display:inline-block; margin-right: 16px;">Exige Logística Reversa?</label>
                    <div class="toggle-group" style="display:inline-flex;">
                        <label><input type="radio" name="has_reverse_logistics" value="1"> Sim</label>
                        <label style="margin-left: 10px;"><input type="radio" name="has_reverse_logistics" value="0" checked> Não</label>
                    </div>
                </div>
                
                <div class="form-group conditional" id="conditional-logistics">
                    <textarea name="reverse_logistics" placeholder="Descreva como o fornecedor deverá recolher embalagens, baterias, etc..."></textarea>
                </div>
            </div>
        </div>

        <!-- =========================================================
             STEP 3: ITENS CATMAT
             ========================================================= -->
        <div class="wizard-step" id="step-3">
            <h2 class="step-title">Itens e Catálogo</h2>
            <p class="step-subtitle">Adicione os itens. A busca no CATMAT/CATSER e a pesquisa de preços básica são automáticas.</p>

            <div id="items-container">
                <!-- Javascript will render item cards here -->
            </div>

            <div style="margin-top: 16px; display: flex; gap: 12px;">
                <button type="button" class="btn btn-secondary" id="btn-add-item">
                    <i class="ph ph-plus"></i> Adicionar Material (CATMAT)
                </button>
                <button type="button" class="btn btn-secondary" id="btn-add-service">
                    <i class="ph ph-plus"></i> Adicionar Serviço (CATSER)
                </button>
            </div>
        </div>

        <!-- =========================================================
             STEP 4: PREÇOS & ENQUADRAMENTO
             ========================================================= -->
        <div class="wizard-step" id="step-4">
            <h2 class="step-title">Preços e Enquadramento Legal</h2>
            <p class="step-subtitle">Resumo financeiro e indicação automática da modalidade de contratação.</p>

            <div class="card" style="text-align: center; padding: 40px 20px;">
                <div style="font-size: 1rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.1em;">Valor Total Estimado</div>
                <div id="display-total-geral" style="font-size: 3rem; font-weight: 800; color: var(--accent); margin: 8px 0 24px;">R$ 0,00</div>
                <input type="hidden" name="study[estimated_total_cost]" id="hidden-total-estimated">
                
                <div id="legal-framing-banner" class="legal-banner licitacao" style="text-align: left; max-width: 600px; margin: 0 auto;">
                    <i class="ph-fill ph-warning-circle icon"></i> 
                    <div>
                        <strong>Aguardando itens...</strong><br>
                        <span style="font-size:0.8rem">O enquadramento legal aparecerá aqui após a adição de itens.</span>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-info">
                <i class="ph ph-info"></i>
                <div>
                    Os preços foram buscados na base do <strong>Painel de Preços (PNCP)</strong> considerando o último ano de contratações públicas para o mesmo código CATMAT/CATSER. Você pode editar os valores unitários na etapa anterior se possuir orçamentos próprios.
                </div>
            </div>
        </div>

        <!-- =========================================================
             STEP 5: ANÁLISES ETP
             ========================================================= -->
        <div class="wizard-step" id="step-5">
            <h2 class="step-title">Estudo Técnico Preliminar (Parte 1)</h2>
            <p class="step-subtitle">As informações a seguir comporão as análises obrigatórias do ETP.</p>

            <div class="card">
                <div class="card-title"><i class="ph ph-calendar-check"></i> Plano de Contratações Anual (PCA)</div>
                <div class="form-group">
                    <label style="display:inline-block; margin-right: 16px;">Esta demanda consta no PCA vigente? *</label>
                    <div class="toggle-group" style="display:inline-flex;">
                        <label><input type="radio" name="study[is_in_pca]" value="1"> Sim</label>
                        <label style="margin-left: 10px;"><input type="radio" name="study[is_in_pca]" value="0" checked> Não</label>
                    </div>
                </div>
                
                <div class="form-row conditional" id="conditional-pca">
                    <div class="form-group">
                        <label>ID/Referência no PCA</label>
                        <input type="text" name="study[pca_reference]">
                    </div>
                    <div class="form-group">
                        <label>Descrição do Item no PCA</label>
                        <input type="text" name="study[pca_description]">
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-title"><i class="ph ph-list-magnifying-glass"></i> Detalhamento da Necessidade (ETP)</div>
                <div class="form-group">
                    <label>Descrição da Necessidade <span class="hint">(ETP Seção 2.1)</span></label>
                    <div class="hint">Descreva o problema identificado e a demanda administrativa de forma clara.</div>
                    <textarea name="study[need_description]" rows="3" placeholder="Se vazio, usará a justificativa geral..."></textarea>
                </div>
                <div class="form-group">
                    <label>Motivação/Justificativa Detalhada <span class="hint">(ETP Seção 2.2)</span></label>
                    <div class="hint">Apresente os motivos que levaram a esta contratação e sua relação com as atividades do órgão.</div>
                    <textarea name="study[motivation]" rows="3" placeholder="Se vazio, usará a justificativa geral..."></textarea>
                </div>
            </div>

            <div class="card">
                <div class="card-title"><i class="ph ph-arrows-split"></i> Alternativas e Parcelamento</div>
                <div class="form-group">
                    <label>Levantamento de Soluções Disponíveis <span class="legal-ref">Art. 18, § 1º, V</span></label>
                    <div class="hint">Descreva as soluções encontradas no mercado que atendem à necessidade.</div>
                    <textarea name="study[solution_mapping]" rows="3">Apenas uma solução identificada no mercado capaz de atender aos requisitos estabelecidos de forma eficiente.</textarea>
                </div>
                
                <div class="form-group">
                    <label>Por que esta solução foi a escolhida? E por que as outras foram descartadas?</label>
                    <textarea name="study[discarded_solutions]" rows="2"></textarea>
                </div>

                <div class="form-group">
                    <label>Justificativa para Parcelamento ou Não Parcelamento <span class="legal-ref">Art. 18, § 1º, VIII</span></label>
                    <textarea name="study[parceling_justification]" rows="2">O objeto não será parcelado em lotes visando a economia de escala e a padronização do fornecimento, além de evitar a gestão de múltiplos contratos para o mesmo fim.</textarea>
                </div>
            </div>
            
            <div class="card">
                <div class="card-title"><i class="ph ph-list-checks"></i> Requisitos e Resultados</div>
                <div class="form-group">
                    <label>Requisitos Técnicos da Solução <span class="legal-ref">Art. 18, § 1º, III</span></label>
                    <textarea name="study[solution_requirements]" rows="3" placeholder="Garantia mínima? Certificação específica? Padrões técnicos?"></textarea>
                </div>
                <div class="form-group">
                    <label>Resultados Pretendidos <span class="legal-ref">Art. 18, § 1º, IX</span></label>
                    <textarea name="study[expected_results]" rows="2" placeholder="O que o órgão espera ganhar/melhorar com essa compra?"></textarea>
                </div>
            </div>
        </div>

        <!-- =========================================================
             STEP 6: VIABILIDADE E PROGRAMA MUNICIPAL
             ========================================================= -->
        <div class="wizard-step" id="step-6">
            <h2 class="step-title">Viabilidade e Programa de Compras</h2>
            <p class="step-subtitle">Avaliação final e vinculação ao fomento local.</p>

            <div class="card">
                <div class="card-title"><i class="ph ph-scales"></i> Análise de Viabilidade</div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Viabilidade Técnica</label>
                        <select name="study[viability_technical]">
                            <option value="Viável. O mercado possui soluções padrão.">Viável. Mercado possui soluções padrão.</option>
                            <option value="Viável com restrições.">Viável com restrições técnicas.</option>
                            <option value="Inviável.">Inviável.</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Viabilidade Econômica</label>
                        <select name="study[viability_economic]">
                            <option value="Viável. Os preços praticados estão condizentes com o mercado público.">Viável. Preços condizentes com o mercado.</option>
                            <option value="Inviável.">Inviável.</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 16px;">
                    <label style="font-size: 1rem; color: var(--text-primary);">Decisão Final da Viabilidade *</label>
                    <div class="toggle-group" style="margin-top: 8px;">
                        <label class="toggle-btn active" style="color:var(--success)"><input type="radio" name="study[viability_decision]" value="viable" checked> A Contratação é Viável</label>
                        <label class="toggle-btn" style="color:var(--warning)"><input type="radio" name="study[viability_decision]" value="viable_with_restrictions"> Viável com Restrições</label>
                        <label class="toggle-btn" style="color:var(--danger)"><input type="radio" name="study[viability_decision]" value="not_viable"> Inviável</label>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-title"><i class="ph ph-storefront"></i> {{ $programaMunicipal['programa'] }}</div>
                
                <div class="form-group">
                    <label style="display:inline-block; margin-right: 16px;">O objeto enquadra-se no Programa Municipal de Compras? *</label>
                    <div class="toggle-group" style="display:inline-flex;">
                        <label><input type="radio" name="study[municipal_program_eligible]" value="1"> Sim</label>
                        <label style="margin-left: 10px;"><input type="radio" name="study[municipal_program_eligible]" value="0" checked> Não</label>
                    </div>
                </div>
                
                <div class="conditional" id="conditional-municipal-program">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Segmento Local</label>
                            <input type="text" name="study[municipal_program_segment]" placeholder="Ex: Serviços Gráficos Locais">
                        </div>
                        <div class="form-group">
                            <label>Recomendação</label>
                            <select name="study[municipal_program_recommendation]">
                                <option value="Lote Exclusivo ME/EPP Local">Lote Exclusivo ME/EPP Local (Até R$ {{ number_format($programaMunicipal['limite_exclusividade_me_epp'], 2, ',', '.') }})</option>
                                <option value="Cota Reservada de {{ $programaMunicipal['percentual_cota_reservada'] }}%">Cota Reservada ({{ $programaMunicipal['percentual_cota_reservada'] }}%)</option>
                                <option value="Margem de Preferência Normal">Margem de Preferência Normal</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Justificativa do Enquadramento</label>
                        <textarea name="study[municipal_program_justification]" rows="2">A contratação fomenta o comércio local e o desenvolvimento municipal, possuindo ao menos 3 fornecedores competitivos na região.</textarea>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-title"><i class="ph ph-users"></i> Equipe de Planejamento</div>
                <div class="form-group">
                    <label>Portaria de Designação</label>
                    <input type="text" name="study[planning_team_portaria]" placeholder="Ex: Portaria nº 123/2026">
                </div>
                
                <label>Membros da Equipe</label>
                <div id="team-members-container" style="margin-top: 8px;">
                    <!-- Team members go here -->
                </div>
                <button type="button" class="btn btn-sm btn-secondary" id="btn-add-team-member" style="margin-top: 8px;">
                    <i class="ph ph-plus"></i> Adicionar Membro
                </button>
            </div>
        </div>

        <!-- =========================================================
             STEP 7: REVISÃO
             ========================================================= -->
        <div class="wizard-step" id="step-7">
            <h2 class="step-title">Revisão e Geração</h2>
            <p class="step-subtitle">Revise as informações antes de gerar os documentos oficiais.</p>

            <div class="card" id="review-summary">
                <!-- Javascript will populate this -->
            </div>
            
            <div class="alert alert-success">
                <i class="ph ph-file-text"></i>
                <div>
                    Ao confirmar, o sistema irá gerar os seguintes documentos em conformidade com os templates oficiais do município:<br>
                    <strong>1. Solicitação de Demanda (SD)</strong><br>
                    <strong>2. Estudo Técnico Preliminar (ETP)</strong>
                </div>
            </div>
        </div>

        <!-- Botões de Navegação -->
        <div class="wizard-nav">
            <button type="button" class="btn btn-secondary" id="btn-prev" style="display: none;">
                <i class="ph ph-arrow-left"></i> Voltar
            </button>
            <div style="flex-grow: 1;"></div>
            <button type="button" class="btn btn-primary" id="btn-next">
                Próximo Passo <i class="ph ph-arrow-right"></i>
            </button>
            <button type="submit" class="btn btn-success" id="btn-submit" style="display: none;">
                <i class="ph ph-check-circle"></i> Gerar Documentos Oficiais
            </button>
        </div>

    </form>
</div>

<script>
    // Pass config data to Javascript safely
    window.WIZARD_DATA = {
        thresholds: @json($thresholds)
    };
</script>
<script src="{{ asset('js/wizard.js') }}"></script>
</body>
</html>