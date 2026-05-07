<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Compras Assaí</title>
    <script>
        (function () {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
    <style>
        :root {
            color-scheme: light;
            --bg: #f8fafc;
            --bg-soft: #f1f5f9;
            --surface: rgba(255, 255, 255, 0.8);
            --surface-strong: rgba(255, 255, 255, 0.9);
            --text: #0f172a;
            --muted: #64748b;
            --accent: #f97316;
            --accent-2: #22c55e;
            --line: rgba(15, 23, 42, 0.08);
            --shadow: 0 30px 80px rgba(15, 23, 42, 0.05);
            --glow-1: rgba(249, 115, 22, 0.12);
            --glow-2: rgba(34, 197, 94, 0.08);
        }

        [data-theme="dark"] {
            color-scheme: dark;
            --bg: #0f172a;
            --bg-soft: #111827;
            --surface: rgba(255, 255, 255, 0.08);
            --surface-strong: rgba(255, 255, 255, 0.14);
            --text: #f8fafc;
            --muted: #cbd5e1;
            --line: rgba(255, 255, 255, 0.12);
            --shadow: 0 30px 80px rgba(15, 23, 42, 0.45);
            --glow-1: rgba(249, 115, 22, 0.3);
            --glow-2: rgba(34, 197, 94, 0.18);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--text);
            background:
                radial-gradient(circle at top left, var(--glow-1), transparent 34%),
                radial-gradient(circle at 80% 20%, var(--glow-2), transparent 24%),
                linear-gradient(135deg, var(--bg), var(--bg-soft));
            font-family: "Inter", "Segoe UI", sans-serif;
            transition: background-color 0.3s, color 0.3s;
        }

        /* ── Floating Theme Toggle ────────────────── */
        .theme-toggle-btn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--surface-strong);
            border: 1px solid var(--line);
            color: var(--text);
            box-shadow: var(--shadow);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(8px);
        }

        .theme-toggle-btn:hover {
            transform: scale(1.1) rotate(15deg);
            border-color: var(--accent);
        }

        .theme-toggle-btn svg {
            width: 20px;
            height: 20px;
            position: absolute;
            transition: transform 0.5s ease, opacity 0.3s ease;
        }

        /* Light mode styles (default) */
        html:not([data-theme="dark"]) .theme-icon-sun {
            opacity: 0;
            transform: rotate(90deg) scale(0);
        }
        html:not([data-theme="dark"]) .theme-icon-moon {
            opacity: 1;
            transform: rotate(0) scale(1);
        }

        /* Dark mode styles */
        html[data-theme="dark"] .theme-icon-sun {
            opacity: 1;
            transform: rotate(0) scale(1);
        }
        html[data-theme="dark"] .theme-icon-moon {
            opacity: 0;
            transform: rotate(-90deg) scale(0);
        }

        .shell {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 32px;
        }

        .panel {
            width: min(1120px, 100%);
            border: 1px solid var(--line);
            background: linear-gradient(180deg, rgba(255,255,255,0.08), rgba(255,255,255,0.04));
            border-radius: 28px;
            box-shadow: var(--shadow);
            overflow: hidden;
            position: relative;
        }

        .panel::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(115deg, rgba(255,255,255,0.06), transparent 35%, rgba(255,255,255,0.04));
            pointer-events: none;
        }

        .hero {
            display: grid;
            grid-template-columns: 1.3fr 0.9fr;
            gap: 28px;
            padding: 40px;
        }

        .eyebrow {
            display: inline-flex;
            gap: 10px;
            align-items: center;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255,255,255,0.08);
            border: 1px solid var(--line);
            color: var(--muted);
            font-size: 0.85rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        h1 {
            margin: 18px 0 14px;
            font-family: "Iowan Old Style", "Palatino Linotype", serif;
            font-size: clamp(2.6rem, 6vw, 5.5rem);
            line-height: 0.95;
            letter-spacing: -0.04em;
        }

        .lede {
            max-width: 62ch;
            font-size: 1.05rem;
            line-height: 1.7;
            color: var(--muted);
            margin: 0;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-top: 28px;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 13px 18px;
            border-radius: 999px;
            border: 1px solid transparent;
            text-decoration: none;
            font-weight: 700;
            transition: transform 160ms ease, border-color 160ms ease, background 160ms ease;
        }

        .button:hover {
            transform: translateY(-1px);
        }

        .button.primary {
            background: var(--accent);
            color: #111827;
        }

        .button.secondary {
            color: var(--text);
            border-color: var(--line);
            background: rgba(255,255,255,0.04);
        }

        .card-grid {
            display: grid;
            gap: 16px;
        }

        .card {
            padding: 18px;
            border-radius: 20px;
            border: 1px solid var(--line);
            background: var(--surface);
            backdrop-filter: blur(12px);
        }

        .card strong {
            display: block;
            font-size: 0.95rem;
            margin-bottom: 8px;
        }

        .card p {
            margin: 0;
            color: var(--muted);
            line-height: 1.6;
            font-size: 0.95rem;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            padding: 0 40px 40px;
        }

        .stat {
            padding: 18px;
            border-radius: 18px;
            border: 1px solid var(--line);
            background: rgba(15, 23, 42, 0.35);
        }

        .stat .label {
            color: var(--muted);
            font-size: 0.85rem;
        }

        .stat .value {
            margin-top: 8px;
            font-size: 1.25rem;
            font-weight: 800;
        }

        @media (max-width: 900px) {
            .hero {
                grid-template-columns: 1fr;
                padding: 28px;
            }

            .stats {
                grid-template-columns: 1fr;
                padding: 0 28px 28px;
            }
        }
    </style>
</head>
<body>
    <main class="shell">
        <section class="panel">
            <div class="hero">
                <div>
                    <div class="eyebrow">Planejamento, pesquisa e rastreabilidade</div>
                    <h1>Compras Assaí</h1>
                    <p class="lede">
                        Base inicial do sistema de contratações com foco em DFD, ETP e integração com o Compras.gov.
                        O núcleo já nasce com schema próprio, regras legais parametrizáveis e endpoints JSON para consulta
                        de catálogo, UASG e preços praticados.
                    </p>
                    <div class="actions">
                        <a class="button primary" href="{{ route('planning.module-one.create') }}">Abrir módulo 1</a>
                        <a class="button secondary" href="/api/compras-gov/material/items?tamanhoPagina=10&statusItem=true">Consultar materiais</a>
                    </div>
                </div>
                <div class="card-grid">
                    <article class="card">
                        <strong>DFD e ETP estruturados</strong>
                        <p>Modelos e tabelas para formalização da demanda, estudo técnico preliminar e itens planejados.</p>
                    </article>
                    <article class="card">
                        <strong>Integração oficial</strong>
                        <p>Cliente HTTP dedicado para o catálogo de materiais, serviços, UASG e pesquisa de preços do Compras.gov.</p>
                    </article>
                    <article class="card">
                        <strong>Regras legais parametrizadas</strong>
                        <p>Valores do art. 75 da Lei 14.133/2021 ficam configuráveis por ambiente e não hardcoded.</p>
                    </article>
                </div>
            </div>
            <div class="stats">
                <div class="stat">
                    <div class="label">Módulo inicial</div>
                    <div class="value">Planejamento da contratação</div>
                </div>
                <div class="stat">
                    <div class="label">API externa</div>
                    <div class="value">Compras.gov Dados Abertos</div>
                </div>
                <div class="stat">
                    <div class="label">Base legal</div>
                    <div class="value">Lei 14.133/2021</div>
                </div>
            </div>
        </section>
    </main>

    <!-- Floating Theme Toggle Button -->
    <button id="theme-toggle-btn" class="theme-toggle-btn" aria-label="Alternar Tema" title="Alternar Tema">
        <!-- Sun icon -->
        <svg class="theme-icon-sun" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="5"></circle>
            <line x1="12" y1="1" x2="12" y2="3"></line>
            <line x1="12" y1="21" x2="12" y2="23"></line>
            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
            <line x1="1" y1="12" x2="3" y2="12"></line>
            <line x1="21" y1="12" x2="23" y2="12"></line>
            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
        </svg>
        <!-- Moon icon -->
        <svg class="theme-icon-moon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
        </svg>
    </button>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('theme-toggle-btn');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
                    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                    document.documentElement.setAttribute('data-theme', newTheme);
                    localStorage.setItem('theme', newTheme);
                });
            }
        });
    </script>
</body>
</html>
