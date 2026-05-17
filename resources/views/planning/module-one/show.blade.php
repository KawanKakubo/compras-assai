<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos - {{ $procurementRequest->reference_code }}</title>
    <script>
        (function () {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="{{ asset('css/documents.css') }}?v={{ time() }}">
    <style>
        /* Pulse Animation for Timeline Indicators */
        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.4); }
            70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(37, 99, 235, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(37, 99, 235, 0); }
        }
        @keyframes pulse-purple {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(124, 58, 237, 0.4); }
            70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(124, 58, 237, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(124, 58, 237, 0); }
        }
        @keyframes pulse-pink {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(219, 39, 119, 0.4); }
            70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(219, 39, 119, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(219, 39, 119, 0); }
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Modern Glassmorphic timeline cards */
        .timeline-step.active {
            border-color: #2563eb !important;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.08);
            background: rgba(37, 99, 235, 0.02) !important;
        }
        .timeline-step.active::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: #2563eb;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }
        .timeline-step.completed {
            border-color: #10b981 !important;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.05);
            background: rgba(16, 185, 129, 0.01) !important;
        }
        
        /* Pulse badges */
        .timeline-step.active .badge-pulse-blue {
            animation: pulse 1.5s infinite;
        }
        .timeline-step.active .badge-pulse-purple {
            animation: pulse-purple 1.5s infinite;
        }
        .timeline-step.active .badge-pulse-pink {
            animation: pulse-pink 1.5s infinite;
        }
    </style>
</head>
<body class="document-view">

    @php
        $user = auth()->user();
        $inicioRoute = match(true) {
            $user?->isSecretaria() => route('secretaria.dashboard'),
            $user?->isGabinete() => route('gabinete.dashboard'),
            $user?->isCompras() => route('compras.dashboard'),
            $user?->isAdmin() => route('admin.dashboard'),
            default => '#',
        };

        // Determine if the currently logged-in user can sign the active stage
        $canUserSignNow = false;
        $buttonLabel = 'Assinar via LibreSign';
        $buttonIcon = 'ph ph-pen-nib';
        
        if ($procurementRequest->status === 'rascunho' && $user?->isElaborador()) {
            $canUserSignNow = true;
            $buttonLabel = 'Assinar e Confirmar Demanda';
        } elseif ($procurementRequest->status === 'assinado' && $user?->isSecretario()) {
            $canUserSignNow = true;
            $buttonLabel = 'Validar e Co-assinar Documento';
        } elseif ($procurementRequest->status === 'em_analise' && $user?->isGabinete()) {
            $canUserSignNow = true;
            $buttonLabel = 'Homologar e Assinar Autorização';
            $buttonIcon = 'ph ph-seal-check';
        }
    @endphp

    <div class="action-bar no-print">
        <div class="action-title">
            <div class="action-title-row">
                <h2>
                    <i class="ph ph-files"></i> 
                    Documentos Gerados — {{ $procurementRequest->reference_code }}
                </h2>
                <span class="status-badge status-{{ $procurementRequest->status }}">
                    {{ str_replace('_', ' ', $procurementRequest->status) }}
                </span>
            </div>
            <span class="action-subtitle">{{ $legalFraming === 'licitacao' ? 'Licitação' : 'Dispensa de Licitação' }}</span>
        </div>
        <div class="action-buttons">
            @if($user?->isSecretaria())
                <a class="btn btn-secondary" href="{{ route('planning.module-one.create') }}">
                    <i class="ph ph-plus"></i> Nova Demanda
                </a>
            @endif
            <a class="btn btn-secondary" href="{{ $inicioRoute }}">
                <i class="ph ph-house"></i> Início
            </a>
            <button class="btn btn-primary" onclick="window.print()">
                <i class="ph ph-printer"></i> Imprimir Página
            </button>
        </div>
    </div>

    <!-- Beautiful Session Alert Blocks -->
    @if(session('success'))
        <div style="max-width: 1200px; margin: 1.5rem auto 0 auto; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: #10b981; padding: 1rem; border-radius: 12px; display: flex; align-items: center; gap: 0.5rem; font-weight: 500; animation: fadeIn 0.4s ease-out; font-family: sans-serif;">
            <i class="ph ph-check-circle" style="font-size: 1.3rem;"></i>
            {{ session('success') }}
        </div>
    @endif
    @if(session('info'))
        <div style="max-width: 1200px; margin: 1.5rem auto 0 auto; background: rgba(37, 99, 235, 0.1); border: 1px solid rgba(37, 99, 235, 0.2); color: #2563eb; padding: 1rem; border-radius: 12px; display: flex; align-items: center; gap: 0.5rem; font-weight: 500; animation: fadeIn 0.4s ease-out; font-family: sans-serif;">
            <i class="ph ph-info" style="font-size: 1.3rem;"></i>
            {{ session('info') }}
        </div>
    @endif

    <!-- Rejection Alert -->
    @if($procurementRequest->status === 'rejeitado' && $procurementRequest->rejection_reason)
        <div style="max-width: 1200px; margin: 1.5rem auto 0 auto; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #ef4444; padding: 1.5rem; border-radius: 12px; animation: fadeIn 0.4s ease-out; font-family: sans-serif;">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem; font-weight: bold;">
                <i class="ph ph-warning-circle" style="font-size: 1.5rem;"></i>
                Solicitação Devolvida para Correção
            </div>
            <div style="font-size: 0.95rem; line-height: 1.5; background: rgba(255, 255, 255, 0.05); padding: 1rem; border-radius: 8px; border-left: 4px solid #ef4444;">
                <strong>Motivo da Devolução:</strong><br>
                {{ $procurementRequest->rejection_reason }}
            </div>
            @if($user?->isElaborador())
                <div style="margin-top: 1rem;">
                    <a href="{{ route('planning.module-one.create', ['edit' => $procurementRequest->id]) }}" class="btn btn-primary" style="background: #ef4444; border-color: #ef4444;">
                        <i class="ph ph-note-pencil"></i> Editar e Corrigir Agora
                    </a>
                </div>
            @endif
        </div>
    @endif

    <div class="document-container">
        <!-- Status Card and Action Panel -->
        <div class="status-card status-{{ $procurementRequest->status }}">
            <div class="status-info">
                <span class="status-label">Status da Solicitação</span>
                <div class="status-value">
                    <div class="status-dot"></div>
                    @php
                        $statusLabels = [
                            'rascunho' => 'Aguardando Assinatura (Elaborador)',
                            'assinado' => 'Aguardando Co-assinatura (Secretário)',
                            'em_analise' => 'Aguardando Homologação (Gabinete)',
                            'aprovado_compras' => 'Aprovado p/ Compras (Finalizado)',
                            'rejeitado' => 'Devolvido p/ Correção',
                            'devolvido' => 'Devolvido p/ Complementação',
                        ];
                    @endphp
                    {{ $statusLabels[$procurementRequest->status] ?? strtoupper(str_replace('_', ' ', $procurementRequest->status)) }}
                </div>
            </div>
            
            <div class="final-actions" style="display: flex; flex-direction: row; gap: 0.75rem; align-items: center;">
                @if($canUserSignNow)
                    <button class="btn-gabinete btn-gabinete-purple" onclick="initializeLibreSign()" id="btn-signature-trigger">
                        <i class="{{ $buttonIcon }}"></i> {{ $buttonLabel }}
                    </button>
                    
                    @if($procurementRequest->current_step !== 'elaborador')
                        <button class="btn-gabinete" onclick="openRejectModal()" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2);">
                            <i class="ph ph-x-circle"></i> Devolver p/ Correção
                        </button>
                    @endif
                @elseif($procurementRequest->status === 'rascunho' || $procurementRequest->status === 'rejeitado')
                    @if($user?->isElaborador())
                        <a href="{{ route('planning.module-one.create', ['edit' => $procurementRequest->id]) }}" class="btn-gabinete" style="background: #2563eb; color: white;">
                            <i class="ph ph-note-pencil"></i> Editar Demanda
                        </a>
                    @endif
                @elseif($procurementRequest->assinatura_status === 'pendente')
                    <button class="btn-gabinete btn-gabinete-success" onclick="verifyActiveSignature()" id="btn-verify-trigger" style="animation: pulse 1.5s infinite;">
                        <i class="ph ph-arrows-counter-clockwise"></i> Verificar Assinatura Pendente
                    </button>
                @else
                    <button class="btn-gabinete" disabled style="background: rgba(148, 163, 184, 0.1); color: #94a3b8; border: 1px solid rgba(148, 163, 184, 0.2);">
                        <i class="ph ph-lock"></i> Aguardando Próxima Etapa
                    </button>
                @endif
            </div>
        </div>

        <!-- Premium PAdES Multi-Signer Timeline -->
        <div class="signature-timeline-container" style="background: var(--card-bg); border: 1px solid var(--border); border-radius: 16px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 4px 20px rgba(0,0,0,0.05); font-family: sans-serif;">
            <h3 style="margin-top: 0; margin-bottom: 1.5rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; color: var(--text-main); font-size: 1.1rem;">
                <i class="ph ph-git-commit" style="color: #2563eb; font-size: 1.4rem;"></i> Fluxo de Assinaturas Digitais Sequenciais (PAdES)
            </h3>
            <div class="timeline-steps" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem; position: relative;">
                
                <!-- Step 1: Elaborador -->
                @php
                    $isElabSigned = in_array($procurementRequest->status, ['assinado', 'em_analise', 'aprovado_compras']);
                    $isElabActive = $procurementRequest->status === 'rascunho';
                @endphp
                <div class="timeline-step {{ $isElabSigned ? 'completed' : ($isElabActive ? 'active' : 'pending') }}" style="position: relative; display: flex; flex-direction: column; gap: 0.5rem; padding: 1.2rem; border-radius: 12px; border: 1px solid var(--border); background: var(--dark-bg); transition: all 0.3s ease;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-size: 0.75rem; font-weight: bold; text-transform: uppercase; color: #2563eb; letter-spacing: 0.5px;">1. Elaboração</span>
                        @if($isElabSigned)
                            <span style="background: rgba(16, 185, 129, 0.1); color: #10b981; font-size: 0.7rem; font-weight: bold; padding: 0.25rem 0.6rem; border-radius: 50px;">ASSINADO</span>
                        @elseif($isElabActive)
                            <span class="badge-pulse-blue" style="background: rgba(37, 99, 235, 0.1); color: #2563eb; font-size: 0.7rem; font-weight: bold; padding: 0.25rem 0.6rem; border-radius: 50px;">AGUARDANDO VOCÊ</span>
                        @else
                            <span style="background: rgba(148, 163, 184, 0.1); color: #94a3b8; font-size: 0.7rem; font-weight: bold; padding: 0.25rem 0.6rem; border-radius: 50px;">PENDENTE</span>
                        @endif
                    </div>
                    <div style="font-weight: 600; color: var(--text-main); font-size: 0.95rem; margin-top: 0.2rem;">Elaborador da Demanda</div>
                    <div style="font-size: 0.82rem; color: var(--text-muted);">{{ $procurementRequest->requester_name ?? 'Não Atribuído' }}</div>
                    @if($isElabSigned)
                        <div style="font-size: 0.78rem; color: #10b981; display: flex; align-items: center; gap: 0.3rem; margin-top: auto; padding-top: 0.5rem; border-top: 1px solid rgba(16, 185, 129, 0.1);">
                            <i class="ph ph-check-circle-fill"></i> Integridade PAdES Homologada
                        </div>
                    @endif
                </div>

                <!-- Step 2: Secretário -->
                @php
                    $isSecSigned = in_array($procurementRequest->status, ['em_analise', 'aprovado_compras']);
                    $isSecActive = $procurementRequest->status === 'assinado';
                @endphp
                <div class="timeline-step {{ $isSecSigned ? 'completed' : ($isSecActive ? 'active' : 'pending') }}" style="position: relative; display: flex; flex-direction: column; gap: 0.5rem; padding: 1.2rem; border-radius: 12px; border: 1px solid var(--border); background: var(--dark-bg); transition: all 0.3s ease;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-size: 0.75rem; font-weight: bold; text-transform: uppercase; color: #7c3aed; letter-spacing: 0.5px;">2. Validação</span>
                        @if($isSecSigned)
                            <span style="background: rgba(16, 185, 129, 0.1); color: #10b981; font-size: 0.7rem; font-weight: bold; padding: 0.25rem 0.6rem; border-radius: 50px;">ASSINADO</span>
                        @elseif($isSecActive)
                            <span class="badge-pulse-purple" style="background: rgba(124, 58, 237, 0.1); color: #7c3aed; font-size: 0.7rem; font-weight: bold; padding: 0.25rem 0.6rem; border-radius: 50px;">AGUARDANDO VOCÊ</span>
                        @else
                            <span style="background: rgba(148, 163, 184, 0.1); color: #94a3b8; font-size: 0.7rem; font-weight: bold; padding: 0.25rem 0.6rem; border-radius: 50px;">PENDENTE</span>
                        @endif
                    </div>
                    <div style="font-weight: 600; color: var(--text-main); font-size: 0.95rem; margin-top: 0.2rem;">Secretário Municipal</div>
                    <div style="font-size: 0.82rem; color: var(--text-muted);">{{ $procurementRequest->responsible_name ?? 'Não Atribuído' }}</div>
                    @if($isSecSigned)
                        <div style="font-size: 0.78rem; color: #10b981; display: flex; align-items: center; gap: 0.3rem; margin-top: auto; padding-top: 0.5rem; border-top: 1px solid rgba(16, 185, 129, 0.1);">
                            <i class="ph ph-check-circle-fill"></i> Validade Administrativa Garantida
                        </div>
                    @endif
                </div>

                <!-- Step 3: Gabinete -->
                @php
                    $isGabSigned = $procurementRequest->status === 'aprovado_compras';
                    $isGabActive = $procurementRequest->status === 'em_analise';
                @endphp
                <div class="timeline-step {{ $isGabSigned ? 'completed' : ($isGabActive ? 'active' : 'pending') }}" style="position: relative; display: flex; flex-direction: column; gap: 0.5rem; padding: 1.2rem; border-radius: 12px; border: 1px solid var(--border); background: var(--dark-bg); transition: all 0.3s ease;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-size: 0.75rem; font-weight: bold; text-transform: uppercase; color: #db2777; letter-spacing: 0.5px;">3. Homologação</span>
                        @if($isGabSigned)
                            <span style="background: rgba(16, 185, 129, 0.1); color: #10b981; font-size: 0.7rem; font-weight: bold; padding: 0.25rem 0.6rem; border-radius: 50px;">HOMOLOGADO</span>
                        @elseif($isGabActive)
                            <span class="badge-pulse-pink" style="background: rgba(219, 39, 119, 0.1); color: #db2777; font-size: 0.7rem; font-weight: bold; padding: 0.25rem 0.6rem; border-radius: 50px;">AGUARDANDO VOCÊ</span>
                        @else
                            <span style="background: rgba(148, 163, 184, 0.1); color: #94a3b8; font-size: 0.7rem; font-weight: bold; padding: 0.25rem 0.6rem; border-radius: 50px;">PENDENTE</span>
                        @endif
                    </div>
                    <div style="font-weight: 600; color: var(--text-main); font-size: 0.95rem; margin-top: 0.2rem;">Gabinete Executivo</div>
                    <div style="font-size: 0.82rem; color: var(--text-muted);">Chefe de Gabinete / Prefeito</div>
                    @if($isGabSigned)
                        <div style="font-size: 0.78rem; color: #10b981; display: flex; align-items: center; gap: 0.3rem; margin-top: auto; padding-top: 0.5rem; border-top: 1px solid rgba(16, 185, 129, 0.1);">
                            <i class="ph ph-check-circle-fill"></i> Autorizado para Lançamento
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Management Area / Document Cards -->
        <div class="management-area">
            <!-- SD Card -->
            <div class="doc-card">
                <div class="icon-wrapper">
                    <i class="ph ph-file-doc"></i>
                </div>
                <div>
                    <h3>Solicitação de Demanda (SD)</h3>
                    <p>Documento oficial que formaliza a necessidade da contratação perante a administração.</p>
                </div>
                <a href="{{ route('planning.module-one.download-sd', $procurementRequest) }}" class="btn-download">
                    <i class="ph ph-download-simple"></i> Baixar DOCX Profissional
                </a>
            </div>

            <!-- ETP Card -->
            <div class="doc-card">
                <div class="icon-wrapper">
                    <i class="ph ph-presentation-chart"></i>
                </div>
                <div>
                    <h3>Estudo Técnico Preliminar (ETP)</h3>
                    <p>Análise detalhada da viabilidade, levantamento de mercado e requisitos da solução.</p>
                </div>
                <a href="{{ route('planning.module-one.download-etp', $procurementRequest) }}" class="btn-download">
                    <i class="ph ph-download-simple"></i> Baixar DOCX Profissional
                </a>
            </div>

            @if($procurementRequest->signed_file_path)
            <!-- Signed PDF Card -->
            <div class="doc-card">
                <div class="icon-wrapper" style="color: #10b981; background: rgba(16, 185, 129, 0.1);">
                    <i class="ph ph-seal-check"></i>
                </div>
                <div>
                    <h3>Documento Assinado Sequencialmente (PAdES)</h3>
                    <p>PDF oficial com assinaturas digitais acopladas, possuindo validade jurídica 100% legal.</p>
                </div>
                <a href="{{ asset('storage/' . $procurementRequest->signed_file_path) }}" target="_blank" class="btn-download" style="background: #10b981;">
                    <i class="ph ph-file-pdf"></i> Ver PDF Integrado
                </a>
            </div>
            @endif
        </div>
    </div>

    <!-- Redirecting Modal -->
    <div id="redirect-modal" class="modal-overlay" style="display: none; font-family: sans-serif;">
        <div class="signature-modal-card" style="padding: 3rem 2rem; border-radius: 20px; text-align: center;">
            <div class="spinner" style="width: 60px; height: 60px; border: 4px solid rgba(124, 58, 237, 0.1); border-left-color: #7c3aed; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 1.5rem;"></div>
            <h3 style="color: var(--text-main); margin-bottom: 0.5rem; font-weight: 600;">Direcionando ao Assinador</h3>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 0;">Preparando ambiente e carregando PDF criptografado do Nextcloud LibreSign...</p>
        </div>
    </div>

    <script>
        // Start signature process
        async function initializeLibreSign() {
            const btn = document.getElementById('btn-signature-trigger');
            const redirectModal = document.getElementById('redirect-modal');
            
            // To ensure popup blockers don't block the new tab, we MUST open it synchronously before the await
            const newTab = window.open('about:blank', '_blank');
            if (!newTab) {
                alert('Por favor, permita pop-ups para este site para abrir o assinador em uma nova aba.');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<i class="ph ph-circle-notch ph-spin"></i> Inicializando...';
            redirectModal.style.display = 'flex';

            try {
                const response = await fetch("{{ route('planning.signature.initialize', $procurementRequest) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                
                const data = await response.json();

                if (data.success && data.sign_url) {
                    newTab.location.href = data.sign_url;
                    
                    btn.innerHTML = '<i class="ph ph-check-circle"></i> Assinatura Iniciada';
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    newTab.close();
                    alert(data.message || 'Falha ao iniciar processo de assinatura.');
                    btn.disabled = false;
                    btn.innerHTML = '{{ $buttonLabel }}';
                    redirectModal.style.display = 'none';
                }
            } catch (e) {
                newTab.close();
                console.error('Error starting signature:', e);
                alert('Ocorreu um erro ao contatar o servidor de assinaturas.');
                btn.disabled = false;
                btn.innerHTML = '{{ $buttonLabel }}';
                redirectModal.style.display = 'none';
            }
        }

        // Verify active pending signature
        async function verifyActiveSignature() {
            const btn = document.getElementById('btn-verify-trigger');
            btn.disabled = true;
            btn.innerHTML = '<i class="ph ph-circle-notch ph-spin"></i> Verificando status...';

            try {
                const response = await fetch("{{ route('planning.signature.verify', $procurementRequest) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    alert(data.message);
                    window.location.href = data.redirect;
                } else {
                    alert(data.message || 'A assinatura ainda não foi concluída no LibreSign.');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="ph ph-arrows-counter-clockwise"></i> Verificar Assinatura Pendente';
                }
            } catch (e) {
                console.error('Error verifying signature:', e);
                alert('Erro ao tentar conectar com a API de verificação.');
                btn.disabled = false;
                btn.innerHTML = '<i class="ph ph-arrows-counter-clockwise"></i> Verificar Assinatura Pendente';
            }
        }

        // Rejection Logic
        function openRejectModal() {
            document.getElementById('reject-modal').style.display = 'flex';
        }

        function closeRejectModal() {
            document.getElementById('reject-modal').style.display = 'none';
        }

        async function submitRejection() {
            const reason = document.getElementById('rejection-reason-input').value;
            const btn = document.getElementById('btn-submit-rejection');

            if (!reason || reason.length < 5) {
                alert('Por favor, informe um motivo válido (mínimo 5 caracteres).');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<i class="ph ph-circle-notch ph-spin"></i> Processando...';

            try {
                const response = await fetch("{{ route('planning.signature.reject', $procurementRequest) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ reason: reason })
                });

                const data = await response.json();

                if (data.success) {
                    alert(data.message);
                    window.location.href = data.redirect;
                } else {
                    alert(data.message || 'Falha ao processar devolução.');
                    btn.disabled = false;
                    btn.innerHTML = 'Confirmar Devolução';
                }
            } catch (e) {
                console.error('Error rejecting:', e);
                alert('Erro ao tentar processar a devolução.');
                btn.disabled = false;
                btn.innerHTML = 'Confirmar Devolução';
            }
        }
    </script>

    <!-- Rejection Modal -->
    <div id="reject-modal" class="modal-overlay" style="display: none; font-family: sans-serif;">
        <div class="signature-modal-card" style="padding: 2rem; border-radius: 20px; width: 100%; max-width: 500px; text-align: left;">
            <h3 style="color: var(--text-main); margin-bottom: 0.5rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                <i class="ph ph-warning-circle" style="color: #ef4444;"></i> Devolver para Correção
            </h3>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 1.5rem;">Explique o motivo da devolução. O elaborador receberá esta mensagem e poderá editar a demanda.</p>
            
            <textarea id="rejection-reason-input" placeholder="Ex: O valor total está incorreto, por favor revise os itens 2 e 3..." 
                style="width: 100%; min-height: 120px; padding: 1rem; border-radius: 12px; border: 1px solid var(--border); background: var(--dark-bg); color: var(--text-main); font-family: inherit; margin-bottom: 1.5rem; resize: vertical; box-sizing: border-box;"></textarea>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button class="btn btn-secondary" onclick="closeRejectModal()" style="border: none; padding: 0.75rem 1.5rem;">Cancelar</button>
                <button id="btn-submit-rejection" class="btn btn-primary" onclick="submitRejection()" style="background: #ef4444; border: none; padding: 0.75rem 1.5rem;">Confirmar Devolução</button>
            </div>
        </div>
    </div>

    <style>
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>

    <!-- Floating Theme Toggle Button -->
    <button id="theme-toggle-btn" class="theme-toggle-btn no-print" aria-label="Alternar Tema" title="Alternar Tema">
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