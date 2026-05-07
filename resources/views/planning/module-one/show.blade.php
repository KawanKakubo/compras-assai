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
    <script src="{{ asset('js/wizard.js') }}?v={{ time() }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize wizard completely empty, ready for new demands
            Wizard.init();
        });
    </script>
</head>
<body class="document-view">

    @php
        $inicioRoute = match(true) {
            auth()->user()?->isSecretaria() => route('secretaria.dashboard'),
            auth()->user()?->isGabinete() => route('gabinete.dashboard'),
            auth()->user()?->isCompras() => route('compras.dashboard'),
            auth()->user()?->isAdmin() => route('admin.dashboard'),
            default => '#',
        };
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
            @if(auth()->user()?->isSecretaria())
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

    <div class="document-container">
        <div class="status-card status-{{ $procurementRequest->status }}">
            <div class="status-info">
                <span class="status-label">Status da Solicitação</span>
                <div class="status-value">
                    <div class="status-dot"></div>
                    @php
                        $statusLabels = [
                            'rascunho' => 'Rascunho / Pendente',
                            'assinado' => 'Assinado (Aguardando Envio)',
                            'em_analise' => 'Em Análise (Gabinete)',
                            'aprovado_compras' => 'Aprovado p/ Compras',
                            'rejeitado' => 'Rejeitado pelo Gabinete',
                            'devolvido' => 'Devolvido p/ Complementação',
                        ];
                    @endphp
                    {{ $statusLabels[$procurementRequest->status] ?? strtoupper(str_replace('_', ' ', $procurementRequest->status)) }}
                </div>
            </div>
            
            <div class="final-actions">
                @if($procurementRequest->canBeSigned() && auth()->user()->isSecretario())
                    @if(config('assinador.bypass') && app()->isLocal())
                        <button class="btn-gabinete btn-gabinete-success" style="margin-bottom: 8px;" onclick="directBypassAuthorize()">
                            <i class="ph ph-check-circle"></i> Autorizar (Modo Teste)
                        </button>
                    @endif
                    <button class="btn-gabinete btn-gabinete-purple" onclick="openSignatureModal()">
                        <i class="ph ph-pen-nib"></i> Assinar Agora
                    </button>
                @elseif($procurementRequest->canBeSubmitted())
                    <form action="{{ route('planning.module-one.submit', $procurementRequest) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn-gabinete btn-gabinete-success">
                            <i class="ph ph-paper-plane-tilt"></i> Enviar para Gabinete
                        </button>
                    </form>
                @elseif($procurementRequest->status === 'em_analise')
                    <button class="btn-gabinete" disabled>
                        <i class="ph ph-check-circle"></i> Já Enviado ao Gabinete
                    </button>
                @endif
            </div>
        </div>

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

            @if($procurementRequest->signed_at)
            <!-- Signed PDF Card -->
            <div class="doc-card">
                <div class="icon-wrapper" style="color: #10b981; background: rgba(16, 185, 129, 0.1);">
                    <i class="ph ph-seal-check"></i>
                </div>
                <div>
                    <h3>Documento Assinado</h3>
                    <p>Versão em PDF com assinaturas digitais avançadas e validade jurídica.</p>
                </div>
                <a href="{{ asset('storage/' . $procurementRequest->signed_file_path) }}" target="_blank" class="btn-download" style="background: #10b981;">
                    <i class="ph ph-file-pdf"></i> Ver PDF Assinado
                </a>
            </div>
            @endif
        </div>
    </div>

    <!-- MFA Signature Modal -->
    <div id="signature-modal" class="modal-overlay" style="display: none;">
        <div class="signature-modal-card">
            <div style="text-align: center; margin-bottom: 24px;">
                <div style="background: rgba(124, 58, 237, 0.1); width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                    <i class="ph ph-shield-check" style="font-size: 32px; color: #7c3aed;"></i>
                </div>
                <h3>Assinatura Digital Avançada</h3>
                <p>Para assinar este documento, enviaremos um código de segurança.</p>
            </div>

            <div id="mfa-step-request">
                <button type="button" class="btn-full" onclick="requestMfa()" id="btn-send-mfa">
                    <i class="ph ph-whatsapp-logo"></i> Enviar Código via WhatsApp
                </button>
            </div>

            <div id="mfa-step-verify" style="display: none;">
                <div class="mfa-input-wrapper">
                    <label>Código de 6 dígitos</label>
                    <input type="text" id="mfa-code" class="mfa-input" maxlength="6" placeholder="000000">
                </div>
                <button type="button" class="btn-full btn-success" onclick="confirmSignature()" id="btn-confirm-sign">
                    Confirmar e Assinar PDF
                </button>
            </div>

            <button type="button" class="btn-cancel" onclick="closeSignatureModal()">Cancelar</button>
        </div>
    </div>

    <script>
        const signatureModal = document.getElementById('signature-modal');
        const mfaStepRequest = document.getElementById('mfa-step-request');
        const mfaStepVerify = document.getElementById('mfa-step-verify');

        function openSignatureModal() {
            signatureModal.style.display = 'flex';
        }

        function closeSignatureModal() {
            signatureModal.style.display = 'none';
            mfaStepRequest.style.display = 'block';
            mfaStepVerify.style.display = 'none';
        }

        let currentChallengeId = null;

        async function requestMfa() {
            const btn = document.getElementById('btn-send-mfa');
            btn.disabled = true;
            btn.innerHTML = '<i class="ph ph-circle-notch ph-spin"></i> Enviando...';

            try {
                const response = await fetch("{{ route('planning.signature.request-mfa', $procurementRequest) }}", {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                const data = await response.json();
                
                if (data.success) {
                    currentChallengeId = data.challenge_id;
                    mfaStepRequest.style.display = 'none';
                    mfaStepVerify.style.display = 'block';
                } else {
                    alert(data.message);
                }
            } catch (e) {
                alert('Erro ao solicitar código. Tente novamente.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="ph ph-whatsapp-logo"></i> Enviar Código via WhatsApp';
            }
        }

        async function confirmSignature() {
            const code = document.getElementById('mfa-code').value;
            if (code.length !== 6) {
                alert('Digite o código de 6 dígitos.');
                return;
            }

            const btn = document.getElementById('btn-confirm-sign');
            btn.disabled = true;
            btn.innerHTML = '<i class="ph ph-circle-notch ph-spin"></i> Processando Assinatura...';

            try {
                const response = await fetch("{{ route('planning.signature.sign', $procurementRequest) }}", {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}' 
                    },
                    body: JSON.stringify({ 
                        mfa_code: code,
                        challenge_id: currentChallengeId
                    })
                });
                const data = await response.json();

                if (data.success) {
                    alert('Documento assinado com sucesso!');
                    window.location.href = data.redirect;
                } else {
                    alert(data.message);
                }
            } catch (e) {
                alert('Erro ao processar assinatura.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'Confirmar e Assinar PDF';
            }
        }
        async function directBypassAuthorize() {
            if (!confirm('Deseja autorizar este documento em modo de teste (sem assinatura real)?')) return;

            const btn = event.currentTarget;
            const originalContent = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="ph ph-circle-notch ph-spin"></i> Autorizando...';

            try {
                const response = await fetch("{{ route('planning.signature.sign', $procurementRequest) }}", {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}' 
                    },
                    body: JSON.stringify({ mfa_code: '000000' }) // Código irrelevante no modo bypass
                });
                const data = await response.json();

                if (data.success) {
                    alert(data.message);
                    window.location.href = data.redirect;
                } else {
                    alert(data.message);
                }
            } catch (e) {
                alert('Erro ao processar autorização.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalContent;
            }
        }
    </script>

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