<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Termo de Referência - {{ $procurementRequest->title }}</title>
    <style>
        :root {
            --bg: #07111f;
            --panel: rgba(9, 18, 34, 0.95);
            --card: rgba(255,255,255,0.05);
            --border: rgba(255,255,255,0.1);
            --text: #e5eefc;
            --muted: #9fb0c8;
            --accent: #f97316;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: var(--text);
            background: linear-gradient(135deg, #020617, var(--bg));
            font-family: Inter, "Segoe UI", sans-serif;
        }
        .page {
            max-width: 1280px;
            margin: 0 auto;
            padding: 28px;
        }
        .hero, .section {
            border: 1px solid var(--border);
            border-radius: 24px;
            background: var(--panel);
            padding: 24px;
            margin-bottom: 18px;
        }
        h1, h2, h3 { margin: 0; }
        h1 {
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(2rem, 4vw, 3.8rem);
            line-height: 0.98;
            margin-bottom: 12px;
        }
        .muted { color: var(--muted); line-height: 1.7; }
        .toolbar { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 18px; }
        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 16px;
            border-radius: 999px;
            text-decoration: none;
            border: 1px solid var(--border);
            color: var(--text);
            background: rgba(255,255,255,0.05);
        }
        .button.primary { background: var(--accent); color: #111827; border-color: transparent; font-weight: 700; }
        .stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-top: 18px; }
        .stat {
            border-radius: 18px;
            border: 1px solid var(--border);
            background: var(--card);
            padding: 16px;
        }
        .stat .label { color: var(--muted); text-transform: uppercase; font-size: 0.78rem; letter-spacing: 0.04em; }
        .stat .value { margin-top: 6px; font-size: 1.05rem; font-weight: 700; }
        .two-column { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .list { display: grid; gap: 12px; }
        .item {
            border-radius: 18px;
            border: 1px solid var(--border);
            background: var(--card);
            padding: 16px;
        }
        .item strong { display: block; margin-bottom: 6px; }
        .item p { margin: 0; color: var(--muted); line-height: 1.6; }
        .table {
            width: 100%;
            border-collapse: collapse;
            overflow: hidden;
        }
        .table th, .table td {
            padding: 12px 10px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            text-align: left;
            vertical-align: top;
        }
        .table th { color: var(--muted); font-weight: 600; font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.05em; }
        .tr-body {
            white-space: pre-wrap;
            line-height: 1.75;
            color: #f8fafc;
        }
        @media (max-width: 900px) {
            .stats, .two-column { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="page">
        <section class="hero">
            <h1>Termo de Referência</h1>
            <p class="muted">
                Documento gerado a partir da solicitação de demanda e do ETP do módulo 1. O texto abaixo consolida o cadastro,
                a justificativa e os itens planejados para apoiar a próxima fase da contratação.
            </p>
            <div class="toolbar">
                <a class="button primary" href="{{ route('planning.module-one.create') }}">Novo módulo 1</a>
                <a class="button" href="/">Página inicial</a>
            </div>
            <div class="stats">
                <div class="stat">
                    <div class="label">Código de referência</div>
                    <div class="value">{{ $procurementRequest->reference_code ?: 'Sem código' }}</div>
                </div>
                <div class="stat">
                    <div class="label">Prioridade</div>
                    <div class="value">{{ ucfirst($procurementRequest->priority_level) }}</div>
                </div>
                <div class="stat">
                    <div class="label">Total estimado</div>
                    <div class="value">R$ {{ number_format((float) ($study?->estimated_total_cost ?? 0), 2, ',', '.') }}</div>
                </div>
            </div>
        </section>

        <section class="section">
            <h2>Solicitação de Demanda</h2>
            <div class="two-column">
                <div class="item">
                    <strong>Título</strong>
                    <p>{{ $procurementRequest->title }}</p>
                </div>
                <div class="item">
                    <strong>Resumo do objeto</strong>
                    <p>{{ $procurementRequest->object_summary }}</p>
                </div>
                <div class="item">
                    <strong>Justificativa da necessidade</strong>
                    <p>{{ $procurementRequest->need_justification }}</p>
                </div>
                <div class="item">
                    <strong>Justificativa da prioridade</strong>
                    <p>{{ $procurementRequest->priority_justification ?: 'Não informada' }}</p>
                </div>
                <div class="item">
                    <strong>Vínculo e contexto</strong>
                    <p>{{ $procurementRequest->linked_request ?: 'Sem solicitação vinculada' }}</p>
                </div>
                <div class="item">
                    <strong>Impactos ambientais</strong>
                    <p>{{ $procurementRequest->environmental_impacts ?: 'Não informado' }}</p>
                </div>
            </div>
        </section>

        <section class="section">
            <h2>Estudo Técnico Preliminar</h2>
            @if ($study)
                <div class="two-column">
                    <div class="item"><strong>Necessidade</strong><p>{{ $study->need_description }}</p></div>
                    <div class="item"><strong>Motivação</strong><p>{{ $study->motivation ?: 'Não informada' }}</p></div>
                    <div class="item"><strong>Requisitos da solução</strong><p>{{ $study->solution_requirements ?: 'Não informados' }}</p></div>
                    <div class="item"><strong>Solução escolhida</strong><p>{{ $study->chosen_solution ?: 'Não definida' }}</p></div>
                    <div class="item"><strong>Viabilidade</strong><p>{{ $study->viability_analysis ?: 'Não informada' }}</p></div>
                    <div class="item"><strong>Decisão</strong><p>{{ str_replace('_', ' ', ucfirst($study->viability_decision)) }}</p></div>
                </div>
            @else
                <p class="muted">Nenhum ETP encontrado para esta demanda.</p>
            @endif
        </section>

        <section class="section">
            <h2>Itens planejados</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Descrição</th>
                        <th>Catálogo</th>
                        <th>Qtd.</th>
                        <th>Valor unitário</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td>{{ ucfirst($item->item_type) }}</td>
                            <td>{{ $item->description }}</td>
                            <td>{{ $item->catalog_code ?: '-' }}</td>
                            <td>{{ rtrim(rtrim(number_format((float) $item->quantity, 4, ',', '.'), '0'), ',') }}</td>
                            <td>{{ $item->unit_value !== null ? 'R$ '.number_format((float) $item->unit_value, 2, ',', '.') : '-' }}</td>
                            <td>{{ $item->total_value !== null ? 'R$ '.number_format((float) $item->total_value, 2, ',', '.') : '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6">Nenhum item informado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section class="section">
            <h2>Texto consolidado do TR</h2>
            <div class="tr-body">{{ $procurementRequest->title }}

Objeto: {{ $procurementRequest->object_summary }}

Justificativa: {{ $procurementRequest->need_justification }}

Solução indicada: {{ $study?->chosen_solution ?: 'a definir com base na pesquisa de mercado e no CATMAT/CATSER' }}

Estimativa total: R$ {{ number_format((float) ($study?->estimated_total_cost ?? 0), 2, ',', '.') }}

Itens planejados: {{ $items->count() }}

Origem técnica: DFD, ETP e consultas ao Compras.gov, com análise de contratações anteriores.</div>
        </section>
    </div>
</body>
</html>