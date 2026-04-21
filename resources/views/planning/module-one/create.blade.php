<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Módulo 1 - Planejamento da Contratação</title>
    <style>
        :root {
            --bg: #07111f;
            --panel: rgba(9, 18, 34, 0.92);
            --card: rgba(255, 255, 255, 0.05);
            --card-strong: rgba(255, 255, 255, 0.08);
            --text: #e5eefc;
            --muted: #9fb0c8;
            --accent: #f97316;
            --accent-2: #22c55e;
            --border: rgba(255, 255, 255, 0.1);
            --danger: #fb7185;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: var(--text);
            background:
                radial-gradient(circle at 20% 0%, rgba(249, 115, 22, 0.23), transparent 30%),
                radial-gradient(circle at 90% 10%, rgba(34, 197, 94, 0.15), transparent 22%),
                linear-gradient(135deg, #020617, var(--bg));
            font-family: Inter, "Segoe UI", sans-serif;
        }

        .page {
            max-width: 1500px;
            margin: 0 auto;
            padding: 28px;
        }

        .hero {
            border: 1px solid var(--border);
            border-radius: 28px;
            padding: 28px;
            background: linear-gradient(180deg, rgba(255,255,255,0.08), rgba(255,255,255,0.03));
            box-shadow: 0 24px 80px rgba(2, 6, 23, 0.45);
        }

        .kicker {
            display: inline-flex;
            padding: 8px 12px;
            border: 1px solid var(--border);
            border-radius: 999px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 0.8rem;
            background: rgba(255,255,255,0.04);
        }

        h1, h2, h3 { margin: 0; }
        h1 {
            margin-top: 16px;
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(2.3rem, 5vw, 4.4rem);
            line-height: 0.95;
        }

        .summary {
            max-width: 80ch;
            color: var(--muted);
            line-height: 1.7;
            margin: 14px 0 0;
        }

        .grid {
            display: grid;
            grid-template-columns: minmax(0, 1.4fr) minmax(320px, 0.85fr);
            gap: 20px;
            margin-top: 20px;
        }

        .panel, .box {
            border: 1px solid var(--border);
            border-radius: 24px;
            background: var(--panel);
        }

        .panel {
            padding: 22px;
        }

        .box {
            padding: 18px;
            margin-bottom: 16px;
            background: rgba(255,255,255,0.04);
        }

        .box h2, .panel h2, .panel h3 {
            font-size: 1.05rem;
            margin-bottom: 14px;
        }

        .fields {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .fields.three {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .field.full { grid-column: 1 / -1; }

        label {
            font-size: 0.82rem;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            color: var(--muted);
        }

        input, select, textarea {
            width: 100%;
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 14px;
            background: rgba(3, 7, 18, 0.7);
            color: var(--text);
            padding: 12px 14px;
            outline: none;
        }

        textarea {
            min-height: 118px;
            resize: vertical;
        }

        input:focus, select:focus, textarea:focus {
            border-color: rgba(249, 115, 22, 0.65);
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.12);
        }

        .error {
            color: var(--danger);
            font-size: 0.82rem;
            line-height: 1.35;
        }

        .toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 16px;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border-radius: 999px;
            border: 1px solid transparent;
            padding: 12px 16px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .button.primary { background: var(--accent); color: #111827; }
        .button.secondary { background: rgba(255,255,255,0.05); color: var(--text); border-color: var(--border); }
        .button.ghost { background: transparent; color: var(--text); border-color: var(--border); }

        .search-box {
            display: grid;
            gap: 12px;
        }

        .results {
            display: grid;
            gap: 10px;
        }

        .result {
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 14px;
            background: rgba(255,255,255,0.04);
        }

        .result strong {
            display: block;
            margin-bottom: 6px;
        }

        .result p {
            margin: 0;
            color: var(--muted);
            line-height: 1.5;
        }

        .item-card {
            border: 1px solid var(--border);
            border-radius: 22px;
            padding: 18px;
            background: rgba(255,255,255,0.04);
            margin-bottom: 16px;
        }

        .item-card header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 16px;
        }

        .item-card header strong { font-size: 1rem; }
        .meta {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .hint {
            color: var(--muted);
            font-size: 0.85rem;
            line-height: 1.5;
        }

        .status {
            margin-top: 10px;
            font-size: 0.9rem;
            color: var(--accent-2);
        }

        .two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .summary-stats {
            display: grid;
            gap: 12px;
            margin-top: 16px;
        }

        .summary-stat {
            padding: 14px;
            border-radius: 16px;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border);
        }

        .summary-stat .label { color: var(--muted); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.04em; }
        .summary-stat .value { margin-top: 6px; font-size: 1.02rem; font-weight: 700; }

        @media (max-width: 1080px) {
            .grid { grid-template-columns: 1fr; }
            .fields, .fields.three, .meta, .two-column { grid-template-columns: 1fr; }
        }

        @media (max-width: 640px) {
            .page { padding: 14px; }
            .hero, .panel { padding: 18px; }
            .toolbar { flex-direction: column; }
        }
    </style>
</head>
<body>
@php
    $oldItems = old('items', []);
    if (empty($oldItems)) {
        $oldItems = [[
            'item_type' => 'material',
            'catalog_code' => '',
            'description' => '',
            'unit' => '',
            'quantity' => 1,
            'unit_value' => '',
            'source_system' => '',
            'source_reference' => '',
            'is_sustainable' => false,
            'notes' => '',
        ]];
    }
    $oldSignatures = old('study.team_signatures', []);
    if (empty($oldSignatures)) {
        $oldSignatures = [[
            'name' => '',
            'role' => '',
            'signature_date' => now()->format('Y-m-d'),
        ]];
    }
@endphp
    <div class="page">
        <section class="hero">
            <div class="kicker">Módulo 1 • formulário inteligente</div>
            <h1>Solicitação de Demanda, ETP e Termo de Referência em um único fluxo</h1>
            <p class="summary">
                O formulário guia a secretaria requisitante pela formalização da demanda, sugere itens a partir do CATMAT/CATSER
                e já prepara a base para o ETP e o termo de referência com rastreabilidade de origem.
            </p>
            <div class="toolbar">
                <a class="button secondary" href="/">Voltar à página inicial</a>
                <button class="button primary" type="button" id="scroll-to-form">Preencher módulo</button>
            </div>
        </section>

        <div class="grid" id="planning-form-anchor">
            <form class="panel" method="post" action="{{ route('planning.module-one.store') }}" id="planning-form">
                @csrf
                @if ($errors->any())
                    <div class="box">
                        <h2>Corrija estes pontos antes de salvar</h2>
                        <div class="results">
                            @foreach ($errors->all() as $error)
                                <div class="result"><p>{{ $error }}</p></div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if (session('status'))
                    <div class="box">
                        <h2>{{ session('status') }}</h2>
                    </div>
                @endif

                <div class="box">
                    <h2>1. Solicitação de Demanda</h2>
                    <div class="fields three">
                        <div class="field">
                            <label for="reference_code">Código de referência</label>
                            <input id="reference_code" name="reference_code" value="{{ old('reference_code') }}" placeholder="REQ-2026-001">
                            @error('reference_code')<span class="error">{{ $message }}</span>@enderror
                        </div>
                        <div class="field">
                            <label for="priority_level">Prioridade</label>
                            <select id="priority_level" name="priority_level">
                                @foreach (['low' => 'Baixa', 'medium' => 'Média', 'high' => 'Alta'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('priority_level', 'medium') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('priority_level')<span class="error">{{ $message }}</span>@enderror
                        </div>
                        <div class="field">
                            <label for="planned_conclusion_at">Concluir até</label>
                            <input id="planned_conclusion_at" type="date" name="planned_conclusion_at" value="{{ old('planned_conclusion_at') }}">
                            @error('planned_conclusion_at')<span class="error">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="fields">
                        <div class="field full">
                            <label for="title">Título da contratação</label>
                            <input id="title" name="title" value="{{ old('title') }}" placeholder="Aquisição de mobiliário ergonômico">
                            @error('title')<span class="error">{{ $message }}</span>@enderror
                        </div>
                        <div class="field full">
                            <label for="object_summary">Resumo do objeto</label>
                            <textarea id="object_summary" name="object_summary" maxlength="200" placeholder="Resumo curto do objeto a ser contratado">{{ old('object_summary') }}</textarea>
                            @error('object_summary')<span class="error">{{ $message }}</span>@enderror
                        </div>
                        <div class="field full">
                            <label for="need_justification">Justificativa da necessidade</label>
                            <textarea id="need_justification" name="need_justification" placeholder="Descreva a necessidade operacional que origina a contratação">{{ old('need_justification') }}</textarea>
                            @error('need_justification')<span class="error">{{ $message }}</span>@enderror
                        </div>
                        <div class="field full">
                            <label for="priority_justification">Justificativa da prioridade</label>
                            <textarea id="priority_justification" name="priority_justification">{{ old('priority_justification') }}</textarea>
                        </div>
                        <div class="field full">
                            <label for="linked_request">Solicitação vinculada</label>
                            <textarea id="linked_request" name="linked_request">{{ old('linked_request') }}</textarea>
                        </div>
                        <div class="field full">
                            <label for="environmental_impacts">Impactos ambientais</label>
                            <textarea id="environmental_impacts" name="environmental_impacts">{{ old('environmental_impacts') }}</textarea>
                        </div>
                        <div class="field full">
                            <label for="reverse_logistics">Logística reversa</label>
                            <textarea id="reverse_logistics" name="reverse_logistics">{{ old('reverse_logistics') }}</textarea>
                        </div>
                    </div>

                    <div class="fields three" style="margin-top: 14px;">
                        <div class="field">
                            <label for="requisition_unit">Unidade requisitante</label>
                            <input id="requisition_unit" name="requisition_unit" value="{{ old('requisition_unit') }}">
                        </div>
                        <div class="field">
                            <label for="requester_name">Requisitante</label>
                            <input id="requester_name" name="requester_name" value="{{ old('requester_name') }}">
                        </div>
                        <div class="field">
                            <label for="requester_cpf">CPF do requisitante</label>
                            <input id="requester_cpf" name="requester_cpf" value="{{ old('requester_cpf') }}" placeholder="000.000.000-00">
                        </div>
                        <div class="field">
                            <label for="requester_role">Cargo do requisitante</label>
                            <input id="requester_role" name="requester_role" value="{{ old('requester_role') }}">
                        </div>
                        <div class="field">
                            <label for="responsible_name">Responsável</label>
                            <input id="responsible_name" name="responsible_name" value="{{ old('responsible_name') }}">
                        </div>
                        <div class="field">
                            <label for="responsible_cpf">CPF do responsável</label>
                            <input id="responsible_cpf" name="responsible_cpf" value="{{ old('responsible_cpf') }}" placeholder="000.000.000-00">
                        </div>
                        <div class="field">
                            <label for="responsible_role">Cargo do responsável</label>
                            <input id="responsible_role" name="responsible_role" value="{{ old('responsible_role') }}">
                        </div>
                        <div class="field">
                            <label for="municipal_policy_applies">Política municipal?</label>
                            <select id="municipal_policy_applies" name="municipal_policy_applies">
                                <option value="0" @selected(!old('municipal_policy_applies'))>Não</option>
                                <option value="1" @selected(old('municipal_policy_applies'))>Sim</option>
                            </select>
                        </div>
                        <div class="field full">
                            <label for="municipal_policy_justification">Justificativa da política municipal</label>
                            <textarea id="municipal_policy_justification" name="municipal_policy_justification">{{ old('municipal_policy_justification') }}</textarea>
                            @error('municipal_policy_justification')<span class="error">{{ $message }}</span>@enderror
                        </div>
                    </div>
                </div>

                <div class="box">
                    <h2>2. Estudo Técnico Preliminar</h2>
                    <div class="fields three">
                        <div class="field">
                            <label for="study_is_in_pca">Consta no PCA?</label>
                            <select id="study_is_in_pca" name="study[is_in_pca]">
                                <option value="0" @selected(!old('study.is_in_pca'))>Não</option>
                                <option value="1" @selected(old('study.is_in_pca'))>Sim</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="study_pca_reference">Referência do PCA</label>
                            <input id="study_pca_reference" name="study[pca_reference]" value="{{ old('study.pca_reference') }}">
                        </div>
                        <div class="field">
                            <label for="study_viability_decision">Decisão de viabilidade</label>
                            <select id="study_viability_decision" name="study[viability_decision]">
                                <option value="viable" @selected(old('study.viability_decision', 'viable') === 'viable')>Viável</option>
                                <option value="viable_with_restrictions" @selected(old('study.viability_decision') === 'viable_with_restrictions')>Viável com restrições</option>
                                <option value="not_viable" @selected(old('study.viability_decision') === 'not_viable')>Não viável</option>
                            </select>
                            @error('study.viability_decision')<span class="error">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="fields">
                        <div class="field full">
                            <label for="study_pca_description">Descrição do PCA</label>
                            <textarea id="study_pca_description" name="study[pca_description]">{{ old('study.pca_description') }}</textarea>
                        </div>
                        <div class="field full">
                            <label for="study_need_description">Descrição da necessidade</label>
                            <textarea id="study_need_description" name="study[need_description]">{{ old('study.need_description') }}</textarea>
                            @error('study.need_description')<span class="error">{{ $message }}</span>@enderror
                        </div>
                        <div class="field full">
                            <label for="study_motivation">Motivação</label>
                            <textarea id="study_motivation" name="study[motivation]">{{ old('study.motivation') }}</textarea>
                        </div>
                        <div class="field full">
                            <label for="study_prerequisites">Pré-requisitos</label>
                            <textarea id="study_prerequisites" name="study[prerequisites]">{{ old('study.prerequisites') }}</textarea>
                        </div>
                        <div class="field full">
                            <label for="study_correlated_contracts">Contratações correlatas</label>
                            <textarea id="study_correlated_contracts" name="study[correlated_contracts]">{{ old('study.correlated_contracts') }}</textarea>
                        </div>
                        <div class="field full">
                            <label for="study_solution_requirements">Requisitos da solução</label>
                            <textarea id="study_solution_requirements" name="study[solution_requirements]">{{ old('study.solution_requirements') }}</textarea>
                        </div>
                        <div class="field full">
                            <label for="study_demand_estimate">Estimativa da demanda</label>
                            <textarea id="study_demand_estimate" name="study[demand_estimate]">{{ old('study.demand_estimate') }}</textarea>
                        </div>
                        <div class="field full">
                            <label for="study_environmental_analysis">Análise ambiental</label>
                            <textarea id="study_environmental_analysis" name="study[environmental_analysis]">{{ old('study.environmental_analysis') }}</textarea>
                        </div>
                        <div class="field full">
                            <label for="study_solution_mapping">Mapeamento da solução</label>
                            <textarea id="study_solution_mapping" name="study[solution_mapping]">{{ old('study.solution_mapping') }}</textarea>
                        </div>
                        <div class="field full">
                            <label for="study_discarded_solutions">Soluções descartadas</label>
                            <textarea id="study_discarded_solutions" name="study[discarded_solutions]">{{ old('study.discarded_solutions') }}</textarea>
                        </div>
                        <div class="field full">
                            <label for="study_parceling_justification">Justificativa do parcelamento</label>
                            <textarea id="study_parceling_justification" name="study[parceling_justification]">{{ old('study.parceling_justification') }}</textarea>
                        </div>
                        <div class="field full">
                            <label for="study_chosen_solution">Solução escolhida</label>
                            <textarea id="study_chosen_solution" name="study[chosen_solution]">{{ old('study.chosen_solution') }}</textarea>
                        </div>
                        <div class="field full">
                            <label for="study_estimated_total_cost">Custo total estimado</label>
                            <input id="study_estimated_total_cost" type="number" step="0.01" name="study[estimated_total_cost]" value="{{ old('study.estimated_total_cost') }}">
                        </div>
                        <div class="field full">
                            <label for="study_expected_results">Resultados esperados</label>
                            <textarea id="study_expected_results" name="study[expected_results]">{{ old('study.expected_results') }}</textarea>
                        </div>
                        <div class="field full">
                            <label for="study_viability_analysis">Análise de viabilidade</label>
                            <textarea id="study_viability_analysis" name="study[viability_analysis]">{{ old('study.viability_analysis') }}</textarea>
                        </div>
                        <div class="field full">
                            <label for="study_municipal_policy_applies">Política municipal no ETP?</label>
                            <select id="study_municipal_policy_applies" name="study[municipal_policy_applies]">
                                <option value="0" @selected(!old('study.municipal_policy_applies'))>Não</option>
                                <option value="1" @selected(old('study.municipal_policy_applies'))>Sim</option>
                            </select>
                        </div>
                        <div class="field full">
                            <label for="study_municipal_policy_analysis">Análise da política municipal</label>
                            <textarea id="study_municipal_policy_analysis" name="study[municipal_policy_analysis]">{{ old('study.municipal_policy_analysis') }}</textarea>
                        </div>
                        <div class="field full">
                            <label for="study_viability_justification">Justificativa da decisão</label>
                            <textarea id="study_viability_justification" name="study[viability_justification]">{{ old('study.viability_justification') }}</textarea>
                        </div>
                    </div>

                    <div style="margin-top: 14px;">
                        <h3>Assinaturas da equipe</h3>
                        <p class="hint">Inclua pelo menos a pessoa responsável pelo estudo e o aprovador, quando aplicável.</p>
                        <div id="signature-list" class="search-box">
                            @foreach ($oldSignatures as $index => $signature)
                                <div class="item-card signature-row" data-index="{{ $index }}">
                                    <div class="meta">
                                        <div class="field">
                                            <label>Nome</label>
                                            <input name="study[team_signatures][{{ $index }}][name]" value="{{ $signature['name'] ?? '' }}">
                                        </div>
                                        <div class="field">
                                            <label>Cargo</label>
                                            <input name="study[team_signatures][{{ $index }}][role]" value="{{ $signature['role'] ?? '' }}">
                                        </div>
                                        <div class="field">
                                            <label>Data</label>
                                            <input type="date" name="study[team_signatures][{{ $index }}][signature_date]" value="{{ $signature['signature_date'] ?? now()->format('Y-m-d') }}">
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <button class="button ghost" type="button" id="add-signature">Adicionar assinatura</button>
                    </div>
                </div>

                <div class="box">
                    <h2>3. Itens planejados</h2>
                    <p class="hint">Use a busca inteligente para localizar itens do CATMAT/CATSER e reaproveitar preços de contratações anteriores.</p>
                    <div id="items-list">
                        @foreach ($oldItems as $index => $item)
                            <article class="item-card item-row" data-index="{{ $index }}">
                                <header>
                                    <strong>Item {{ $index + 1 }}</strong>
                                    <button class="button ghost remove-item" type="button">Remover</button>
                                </header>
                                <div class="meta">
                                    <div class="field">
                                        <label>Tipo</label>
                                        <select name="items[{{ $index }}][item_type]">
                                            <option value="material" @selected(($item['item_type'] ?? 'material') === 'material')>Material</option>
                                            <option value="service" @selected(($item['item_type'] ?? 'material') === 'service')>Serviço</option>
                                        </select>
                                    </div>
                                    <div class="field">
                                        <label>Código do catálogo</label>
                                        <input name="items[{{ $index }}][catalog_code]" value="{{ $item['catalog_code'] ?? '' }}">
                                    </div>
                                    <div class="field">
                                        <label>Unidade</label>
                                        <input name="items[{{ $index }}][unit]" value="{{ $item['unit'] ?? '' }}">
                                    </div>
                                    <div class="field full">
                                        <label>Descrição</label>
                                        <input name="items[{{ $index }}][description]" value="{{ $item['description'] ?? '' }}" placeholder="Digite o texto e busque no catálogo">
                                    </div>
                                    <div class="field">
                                        <label>Quantidade</label>
                                        <input type="number" step="0.0001" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] ?? 1 }}">
                                    </div>
                                    <div class="field">
                                        <label>Valor unitário</label>
                                        <input type="number" step="0.01" name="items[{{ $index }}][unit_value]" value="{{ $item['unit_value'] ?? '' }}">
                                    </div>
                                    <div class="field">
                                        <label>Sistema de origem</label>
                                        <input name="items[{{ $index }}][source_system]" value="{{ $item['source_system'] ?? '' }}" placeholder="compras_gov">
                                    </div>
                                    <div class="field">
                                        <label>Referência da origem</label>
                                        <input name="items[{{ $index }}][source_reference]" value="{{ $item['source_reference'] ?? '' }}">
                                    </div>
                                    <div class="field">
                                        <label>Sustentável?</label>
                                        <select name="items[{{ $index }}][is_sustainable]">
                                            <option value="0" @selected(empty($item['is_sustainable']))>Não</option>
                                            <option value="1" @selected(!empty($item['is_sustainable']))>Sim</option>
                                        </select>
                                    </div>
                                    <div class="field full">
                                        <label>Observações</label>
                                        <textarea name="items[{{ $index }}][notes]">{{ $item['notes'] ?? '' }}</textarea>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                    <div class="toolbar">
                        <button class="button ghost" type="button" id="add-item">Adicionar item</button>
                    </div>
                </div>

                <div class="toolbar">
                    <button class="button primary" type="submit">Salvar módulo 1</button>
                    <button class="button secondary" type="button" id="fill-example">Preencher exemplo</button>
                </div>
            </form>

            <aside>
                <div class="panel">
                    <h2>Busca inteligente</h2>
                    <p class="hint">Aqui a secretaria pode pesquisar o catálogo oficial e comparar com contratações antigas antes de gravar a demanda.</p>

                    <div class="box">
                        <h3>CATMAT / CATSER</h3>
                        <div class="search-box">
                            <div class="fields">
                                <div class="field full">
                                    <label for="lookup-query">Descrição ou palavra-chave</label>
                                    <input id="lookup-query" placeholder="cadeira ergonômica">
                                </div>
                                <div class="field">
                                    <label for="lookup-type">Busca</label>
                                    <select id="lookup-type">
                                        <option value="material">Material</option>
                                        <option value="service">Serviço</option>
                                    </select>
                                </div>
                                <div class="field">
                                    <label for="lookup-page-size">Itens por página</label>
                                    <input id="lookup-page-size" type="number" value="10" min="10" max="500">
                                </div>
                            </div>
                            <button type="button" class="button primary" id="lookup-button">Pesquisar catálogo</button>
                            <div class="results" id="lookup-results"></div>
                        </div>
                    </div>

                    <div class="box">
                        <h3>Licitações antigas</h3>
                        <p class="hint">Depois de selecionar um código de catálogo, use a pesquisa histórica para refinar o preço estimado.</p>
                        <div class="search-box">
                            <div class="fields">
                                <div class="field full">
                                    <label for="price-code">Código do item catalogado</label>
                                    <input id="price-code" placeholder="206504">
                                </div>
                                <div class="field">
                                    <label for="price-search-type">Origem</label>
                                    <select id="price-search-type">
                                        <option value="material">Material</option>
                                        <option value="service">Serviço</option>
                                    </select>
                                </div>
                                <div class="field">
                                    <label for="price-page-size">Itens por página</label>
                                    <input id="price-page-size" type="number" value="10" min="10" max="500">
                                </div>
                            </div>
                            <button type="button" class="button secondary" id="price-button">Buscar preços</button>
                            <div class="results" id="price-results"></div>
                        </div>
                    </div>

                    <div class="summary-stats">
                        <div class="summary-stat">
                            <div class="label">Art. 75 inciso I</div>
                            <div class="value">R$ {{ number_format($thresholds['inciso_i'] ?? 0, 2, ',', '.') }}</div>
                        </div>
                        <div class="summary-stat">
                            <div class="label">Art. 75 inciso II</div>
                            <div class="value">R$ {{ number_format($thresholds['inciso_ii'] ?? 0, 2, ',', '.') }}</div>
                        </div>
                        <div class="summary-stat">
                            <div class="label">Saída do módulo</div>
                            <div class="value">DFD, ETP e TR</div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <template id="item-template">
        <article class="item-card item-row" data-index="__INDEX__">
            <header>
                <strong>Item __NUMBER__</strong>
                <button class="button ghost remove-item" type="button">Remover</button>
            </header>
            <div class="meta">
                <div class="field">
                    <label>Tipo</label>
                    <select name="items[__INDEX__][item_type]">
                        <option value="material">Material</option>
                        <option value="service">Serviço</option>
                    </select>
                </div>
                <div class="field">
                    <label>Código do catálogo</label>
                    <input name="items[__INDEX__][catalog_code]">
                </div>
                <div class="field">
                    <label>Unidade</label>
                    <input name="items[__INDEX__][unit]">
                </div>
                <div class="field full">
                    <label>Descrição</label>
                    <input name="items[__INDEX__][description]">
                </div>
                <div class="field">
                    <label>Quantidade</label>
                    <input type="number" step="0.0001" name="items[__INDEX__][quantity]" value="1">
                </div>
                <div class="field">
                    <label>Valor unitário</label>
                    <input type="number" step="0.01" name="items[__INDEX__][unit_value]">
                </div>
                <div class="field">
                    <label>Sistema de origem</label>
                    <input name="items[__INDEX__][source_system]" placeholder="compras_gov">
                </div>
                <div class="field">
                    <label>Referência da origem</label>
                    <input name="items[__INDEX__][source_reference]">
                </div>
                <div class="field">
                    <label>Sustentável?</label>
                    <select name="items[__INDEX__][is_sustainable]">
                        <option value="0">Não</option>
                        <option value="1">Sim</option>
                    </select>
                </div>
                <div class="field full">
                    <label>Observações</label>
                    <textarea name="items[__INDEX__][notes]"></textarea>
                </div>
            </div>
        </article>
    </template>

    <template id="signature-template">
        <div class="item-card signature-row" data-index="__INDEX__">
            <div class="meta">
                <div class="field">
                    <label>Nome</label>
                    <input name="study[team_signatures][__INDEX__][name]">
                </div>
                <div class="field">
                    <label>Cargo</label>
                    <input name="study[team_signatures][__INDEX__][role]">
                </div>
                <div class="field">
                    <label>Data</label>
                    <input type="date" name="study[team_signatures][__INDEX__][signature_date]" value="{{ now()->format('Y-m-d') }}">
                </div>
            </div>
        </div>
    </template>

    <script>
        const form = document.getElementById('planning-form');
        const lookupButton = document.getElementById('lookup-button');
        const lookupResults = document.getElementById('lookup-results');
        const priceButton = document.getElementById('price-button');
        const priceResults = document.getElementById('price-results');
        const itemsList = document.getElementById('items-list');
        const itemTemplate = document.getElementById('item-template').innerHTML;
        const signatureList = document.getElementById('signature-list');
        const signatureTemplate = document.getElementById('signature-template').innerHTML;

        function nextIndex(container, selector) {
            const indexes = Array.from(container.querySelectorAll(selector)).map((element) => Number(element.dataset.index));
            return indexes.length ? Math.max(...indexes) + 1 : 0;
        }

        function renderResults(container, payload, onSelect) {
            container.innerHTML = '';

            const rows = payload?.resultado || payload?.dados || payload?.items || [];

            if (!rows.length) {
                container.innerHTML = '<div class="result"><p>Nenhum resultado encontrado.</p></div>';
                return;
            }

            rows.slice(0, 8).forEach((row) => {
                const code = row.codigoItem || row.codigoServico || row.codigoUasg || row.codigoItemCatalogo || '';
                const title = row.descricaoItem || row.nomeItem || row.descricaoServico || row.nomeServico || row.nomeUasg || 'Resultado';
                const subline = [
                    row.nomeGrupo,
                    row.nomeClasse,
                    row.nomePdm,
                    row.unidadeFornecimento,
                    row.siglaUf,
                    row.municipio,
                    row.nomeUasg,
                    row.valorUnitario,
                ].filter(Boolean).join(' • ');

                const result = document.createElement('button');
                result.type = 'button';
                result.className = 'result';
                result.innerHTML = '<strong>' + (code ? '[' + code + '] ' : '') + title + '</strong><p>' + subline + '</p>';
                result.addEventListener('click', () => onSelect(row));
                container.appendChild(result);
            });
        }

        lookupButton.addEventListener('click', async () => {
            const query = document.getElementById('lookup-query').value.trim();
            const lookupType = document.getElementById('lookup-type').value;
            const pageSize = document.getElementById('lookup-page-size').value || 10;
            const params = new URLSearchParams({ tamanhoPagina: pageSize });

            if (query) {
                params.set('descricaoItem', query);
            }

            try {
                const endpoint = lookupType === 'service'
                    ? '/api/compras-gov/service/items'
                    : '/api/compras-gov/material/items';

                const response = await fetch(endpoint + '?' + params.toString(), {
                    headers: { 'Accept': 'application/json' },
                });

                const payload = await response.json();
                renderResults(lookupResults, payload, (row) => {
                    const firstItem = document.querySelector('.item-row');
                    if (!firstItem) {
                        document.getElementById('add-item').click();
                    }

                    const activeItem = document.querySelector('.item-row:last-child');
                    if (activeItem) {
                        const codeField = activeItem.querySelector('input[name$="[catalog_code]"]');
                        const descriptionField = activeItem.querySelector('input[name$="[description]"]');
                        const unitField = activeItem.querySelector('input[name$="[unit]"]');
                        const sourceSystemField = activeItem.querySelector('input[name$="[source_system]"]');

                        if (codeField) codeField.value = row.codigoItem || row.codigoServico || row.codigoUasg || row.codigoItemCatalogo || '';
                        if (descriptionField) descriptionField.value = row.descricaoItem || row.nomeItem || row.descricaoServico || row.nomeServico || row.nomeUasg || '';
                        if (unitField) unitField.value = row.unidadeFornecimento || row.nomeUnidade || '';
                        if (sourceSystemField) sourceSystemField.value = 'compras_gov';
                    }

                    if (row.codigoItem || row.codigoServico || row.codigoItemCatalogo) {
                        document.getElementById('price-code').value = row.codigoItem || row.codigoServico || row.codigoItemCatalogo;
                    }
                });
            } catch (error) {
                lookupResults.innerHTML = '<div class="result"><p>Falha ao consultar o catálogo.</p></div>';
            }
        });

        priceButton.addEventListener('click', async () => {
            const code = document.getElementById('price-code').value.trim();
            const pageSize = document.getElementById('price-page-size').value || 10;
            const searchType = document.getElementById('price-search-type').value;

            if (!code) {
                priceResults.innerHTML = '<div class="result"><p>Informe um código de item catalogado.</p></div>';
                return;
            }

            const params = new URLSearchParams({ codigoItemCatalogo: code, tamanhoPagina: pageSize });

            try {
                const endpoint = searchType === 'service'
                    ? '/api/compras-gov/service/prices'
                    : '/api/compras-gov/material/prices';

                const response = await fetch(endpoint + '?' + params.toString(), {
                    headers: { 'Accept': 'application/json' },
                });

                const payload = await response.json();
                renderResults(priceResults, payload, (row) => {
                    const activeItem = document.querySelector('.item-row:last-child');
                    if (!activeItem) {
                        return;
                    }

                    const unitValueField = activeItem.querySelector('input[name$="[unit_value]"]');
                    const sourceReferenceField = activeItem.querySelector('input[name$="[source_reference]"]');

                    if (unitValueField && row.valorUnitario !== undefined) {
                        unitValueField.value = row.valorUnitario;
                    }

                    if (sourceReferenceField) {
                        sourceReferenceField.value = row.idCompra || row.codigoCompra || row.numeroCompra || '';
                    }
                });
            } catch (error) {
                priceResults.innerHTML = '<div class="result"><p>Falha ao consultar preços históricos.</p></div>';
            }
        });

        document.getElementById('add-item').addEventListener('click', () => {
            const index = nextIndex(itemsList, '.item-row');
            const wrapper = document.createElement('div');
            wrapper.innerHTML = itemTemplate.replaceAll('__INDEX__', index).replaceAll('__NUMBER__', index + 1);
            itemsList.appendChild(wrapper.firstElementChild);
        });

        document.getElementById('add-signature').addEventListener('click', () => {
            const index = nextIndex(signatureList, '.signature-row');
            const wrapper = document.createElement('div');
            wrapper.innerHTML = signatureTemplate.replaceAll('__INDEX__', index);
            signatureList.appendChild(wrapper.firstElementChild);
        });

        document.addEventListener('click', (event) => {
            if (!event.target.classList.contains('remove-item')) {
                return;
            }

            const card = event.target.closest('.item-row');
            if (card && document.querySelectorAll('.item-row').length > 1) {
                card.remove();
            }
        });

        document.getElementById('fill-example').addEventListener('click', () => {
            document.getElementById('title').value = 'Aquisição de cadeiras ergonômicas';
            document.getElementById('object_summary').value = 'Cadeiras ergonômicas para adequação de postos de trabalho.';
            document.getElementById('need_justification').value = 'Substituição de mobiliário danificado e adequação ergonômica da operação.';
            document.getElementById('study_need_description').value = 'Necessidade de readequação dos postos para reduzir riscos ocupacionais.';
            document.getElementById('lookup-query').value = 'cadeira ergonômica';
        });

        document.getElementById('scroll-to-form').addEventListener('click', () => {
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    </script>
</body>
</html>