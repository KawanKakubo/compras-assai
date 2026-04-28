<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos - {{ $procurementRequest->reference_code }}</title>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="{{ asset('css/documents.css') }}">
    <script src="{{ asset('js/wizard.js') }}?v={{ time() }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize wizard completely empty, ready for new demands
            Wizard.init();
        });
    </script>
</head>
<body class="document-view">

    <div class="action-bar no-print">
        <div>
            <h2 style="margin: 0; font-size: 1.2rem; display: flex; align-items: center; gap: 10px;">
                <i class="ph ph-files"></i> 
                Documentos Gerados — {{ $procurementRequest->reference_code }}
                <span class="status-badge status-{{ $procurementRequest->status }}" style="font-size: 0.7rem; padding: 4px 8px; border-radius: 6px; background: rgba(255,255,255,0.1); text-transform: uppercase;">
                    {{ str_replace('_', ' ', $procurementRequest->status) }}
                </span>
            </h2>
            <span style="font-size: 0.85rem; color: #9ca3af;">{{ $legalFraming === 'licitacao' ? 'Licitação' : 'Dispensa de Licitação' }}</span>
        </div>
        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <a class="btn" style="background: #374151; text-decoration: none;" href="{{ route('planning.module-one.create') }}">
                <i class="ph ph-plus"></i> Nova Demanda
            </a>
            <a class="btn" style="background: #1e293b; text-decoration: none;" href="{{ auth()->user()->isSecretaria() ? route('secretaria.dashboard') : '#' }}">
                <i class="ph ph-house"></i> Início
            </a>
            <button class="btn" style="background: #4b5563;" onclick="window.print()">
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
                    <button class="btn-gabinete" style="background: #7c3aed; box-shadow: 0 4px 14px rgba(124, 58, 237, 0.4);" onclick="openSignatureModal()">
                        <i class="ph ph-pen-nib"></i> Assinar Agora
                    </button>
                @elseif($procurementRequest->canBeSubmitted())
                    <form action="{{ route('planning.module-one.submit', $procurementRequest) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn-gabinete">
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
                <h3 style="margin: 0; color: #f8fafc;">Assinatura Digital Avançada</h3>
                <p style="font-size: 0.9rem; color: #94a3b8; margin-top: 8px;">Para assinar este documento, enviaremos um código de segurança.</p>
            </div>

            <div id="mfa-step-request">
                <button type="button" class="btn-full" onclick="requestMfa()" id="btn-send-mfa">
                    <i class="ph ph-whatsapp-logo"></i> Enviar Código via WhatsApp
                </button>
            </div>

            <div id="mfa-step-verify" style="display: none;">
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 0.8rem; color: #94a3b8; margin-bottom: 8px;">Código de 6 dígitos</label>
                    <input type="text" id="mfa-code" maxlength="6" style="width: 100%; background: rgba(15, 23, 42, 0.5); border: 1px solid #334155; padding: 12px; border-radius: 8px; color: white; font-size: 1.2rem; text-align: center; letter-spacing: 4px;">
                </div>
                <button type="button" class="btn-full" style="background: #10b981;" onclick="confirmSignature()" id="btn-confirm-sign">
                    Confirmar e Assinar PDF
                </button>
            </div>

            <button type="button" class="btn-cancel" onclick="closeSignatureModal()">Cancelar</button>
        </div>
    </div>

    <style>
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(8px);
            display: flex; align-items: center; justify-content: center; z-index: 1000;
        }
        .signature-modal-card {
            background: #1e293b; border: 1px solid #334155; border-radius: 20px;
            padding: 32px; width: 400px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .btn-full {
            width: 100%; background: #2563eb; color: white; border: none;
            padding: 14px; border-radius: 12px; font-weight: 600; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            margin-bottom: 12px; transition: 0.2s;
        }
        .btn-full:hover { filter: brightness(1.1); }
        .btn-cancel {
            width: 100%; background: transparent; color: #94a3b8; border: none;
            padding: 8px; font-size: 0.9rem; cursor: pointer;
        }
        @media print { .modal-overlay { display: none !important; } }
    </style>

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
                    body: JSON.stringify({ mfa_code: code })
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
    </script>
</body>
</html>