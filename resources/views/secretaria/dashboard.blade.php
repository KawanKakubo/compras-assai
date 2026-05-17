@extends('layouts.dashboard')

@section('title', 'Dashboard Secretaria')
@section('header_title', 'Minhas Requisições')
@section('header_subtitle', 'Acompanhe o status de suas solicitações de compra.')

@section('content')
<div class="stats-grid">
    <div class="card">
        <p class="card-title">Rascunhos</p>
        <p class="card-value">{{ $stats['draft'] }}</p>
    </div>
    <div class="card">
        <p class="card-title">Assinados</p>
        <p class="card-value">{{ $stats['signed'] }}</p>
    </div>
    <div class="card">
        <p class="card-title">Em Análise</p>
        <p class="card-value">{{ $stats['analysis'] }}</p>
    </div>
    <div class="card">
        <p class="card-title">Devolvidos/Rejeitados</p>
        <p class="card-value" style="color: var(--danger);">{{ $stats['returned'] }}</p>
    </div>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h3>Solicitações da Secretaria</h3>
        <a href="{{ route('planning.module-one.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Nova Solicitação
        </a>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Título</th>
                    <th>Data</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $req)
                <tr>
                    <td><strong>{{ $req->reference_code }}</strong></td>
                    <td>{{ $req->title }}</td>
                    <td>{{ $req->created_at->format('d/m/Y') }}</td>
                    <td>
                        @php
                            $statusClasses = [
                                'rascunho' => 'badge-secondary',
                                'assinado' => 'badge-info',
                                'em_analise' => 'badge-pending',
                                'aprovado_compras' => 'badge-success',
                                'rejeitado' => 'badge-danger',
                                'devolvido' => 'badge-warning',
                            ];
                            $statusLabels = [
                                'rascunho' => 'Rascunho',
                                'assinado' => 'Assinado',
                                'em_analise' => 'Em Análise',
                                'aprovado_compras' => 'Aprovado',
                                'rejeitado' => 'Rejeitado',
                                'devolvido' => 'Devolvido',
                            ];
                        @endphp
                        <span class="badge {{ $statusClasses[$req->status] ?? 'badge-secondary' }}">
                            {{ $statusLabels[$req->status] ?? ucfirst(str_replace('_', ' ', $req->status)) }}
                        </span>
                    </td>
                    <td>
                        @php
                            $canSign = false;
                            $user = auth()->user();
                            if (($req->status === 'assinado' || $req->status === 'rascunho') && $user?->isSecretario()) {
                                $canSign = true;
                            } elseif ($req->status === 'em_analise' && $user?->isGabinete()) {
                                $canSign = true;
                            }
                        @endphp
                        <button class="nav-link btn-view-request" 
                                data-id="{{ $req->id }}" 
                                data-code="{{ $req->reference_code }}" 
                                data-title="{{ $req->title }}" 
                                data-cansign="{{ $canSign ? 'true' : 'false' }}"
                                data-sd-status="{{ $req->metadata['signatures']['sd']['status'] ?? '' }}"
                                data-etp-status="{{ $req->metadata['signatures']['etp']['status'] ?? '' }}"
                                style="padding: 0.5rem; display: inline-flex; border: none; background: none; cursor: pointer; color: var(--text-main);" 
                                title="Visualizar">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                        @if($req->canBeEditedBy(auth()->user()))
                        <a href="{{ route('planning.module-one.create', ['edit' => $req->id]) }}" class="nav-link" style="padding: 0.5rem; display: inline-flex; color: var(--warning);" title="Editar">
                            <i class="fa-solid fa-pencil"></i>
                        </a>
                        <form action="{{ route('planning.module-one.destroy', $req->id) }}" method="POST" style="display: inline-flex;" onsubmit="return confirm('Tem certeza que deseja inativar esta demanda?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="nav-link" style="padding: 0.5rem; border: none; background: none; color: var(--danger); cursor: pointer;" title="Inativar">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                        Nenhuma solicitação encontrada. Comece criando uma nova!
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Estilos do Modal (Tema Claro/Escuro) -->
<style>
    /* Modal Base (Tema Claro Default) */
    #document-modal .modal-card {
        background: #ffffff;
        color: #1f2937;
        border: 1px solid #e5e7eb;
    }
    #document-modal .modal-card h3 {
        color: #111827 !important;
    }
    #document-modal .modal-card h4 {
        color: #1f2937 !important;
    }
    #document-modal .modal-card p, 
    #document-modal .modal-card span {
        color: #4b5563 !important;
    }
    #document-modal .modal-card strong {
        color: #111827 !important;
    }
    #document-modal .modal-card .summary-box {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
    }
    #document-modal .modal-card .document-row {
        background: #ffffff;
        border: 1px solid #e5e7eb;
    }
    #document-modal .modal-card .document-row:hover {
        background: #f9fafb;
    }
    #document-modal .modal-card .btn-secondary {
        background: #ffffff;
        color: #374151;
        border: 1px solid #d1d5db;
    }
    #document-modal .modal-card .btn-secondary:hover {
        background: #f3f4f6;
    }

    /* Tema Escuro (Ativado pela classe ou atributo no HTML) */
    [data-theme="dark"] #document-modal .modal-card {
        background: #1a1d20;
        color: #f3f4f6;
        border: 1px solid rgba(255,255,255,0.1);
    }
    [data-theme="dark"] #document-modal .modal-card h3 {
        color: #ffffff !important;
    }
    [data-theme="dark"] #document-modal .modal-card h4 {
        color: #f3f4f6 !important;
    }
    [data-theme="dark"] #document-modal .modal-card p, 
    [data-theme="dark"] #document-modal .modal-card span {
        color: #9ca3af !important;
    }
    [data-theme="dark"] #document-modal .modal-card strong {
        color: #ffffff !important;
    }
    [data-theme="dark"] #document-modal .modal-card .summary-box {
        background: rgba(255,255,255,0.02);
        border: 1px solid rgba(255,255,255,0.05);
    }
    [data-theme="dark"] #document-modal .modal-card .document-row {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.03);
    }
    [data-theme="dark"] #document-modal .modal-card .document-row:hover {
        background: rgba(255,255,255,0.05);
        border-color: rgba(255,255,255,0.1);
    }
    [data-theme="dark"] #document-modal .modal-card .btn-secondary {
        background: rgba(255,255,255,0.05);
        color: #ffffff;
        border: 1px solid rgba(255,255,255,0.1);
    }
    [data-theme="dark"] #document-modal .modal-card .btn-secondary:hover {
        background: rgba(255,255,255,0.1);
    }
</style>

<!-- Modal de Documentos -->
<div id="document-modal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000; justify-content: center; align-items: center; backdrop-filter: blur(4px);">
    <div class="modal-card" style="border-radius: 16px; width: 100%; max-width: 650px; padding: 2rem; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 id="modal-title" style="margin: 0; font-size: 1.25rem; font-weight: 600;">Documentos da Demanda</h3>
            <button onclick="closeModal()" style="background: none; border: none; color: inherit; cursor: pointer; font-size: 1.5rem; padding: 0.5rem; display: flex; align-items: center; justify-content: center; border-radius: 50%; width: 36px; height: 36px; opacity: 0.7;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.7'">&times;</button>
        </div>
        
        <div class="summary-box" style="margin-bottom: 1.5rem; padding: 1rem; border-radius: 8px;">
            <p style="margin: 0.2rem 0; font-size: 0.9rem;"><strong>Código:</strong> <span id="modal-req-code"></span></p>
            <p style="margin: 0.2rem 0; font-size: 0.9rem;"><strong>Título:</strong> <span id="modal-req-title"></span></p>
        </div>

        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <!-- Linha 1: SD -->
            <div class="document-row" style="display: flex; justify-content: space-between; align-items: center; padding: 1.25rem; border-radius: 12px; transition: all 0.2s;">
                <div>
                    <h4 style="margin: 0; font-size: 1rem; font-weight: 500;">Solicitação de Demanda (SD)</h4>
                    <p style="margin: 0.2rem 0; font-size: 0.85rem;">Documento inicial com a justificativa e itens.</p>
                </div>
                <div style="display: flex; gap: 0.75rem;">
                    <a id="btn-download-sd" href="#" class="btn btn-secondary" style="padding: 0.6rem 1.25rem; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 0.5rem; border-radius: 8px; text-decoration: none; transition: all 0.2s;">
                        <i class="fa-solid fa-download"></i> Download
                    </a>
                    <button id="btn-sign-sd" class="btn btn-primary btn-sign" style="padding: 0.6rem 1.25rem; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 0.5rem; border-radius: 8px; background: #2563eb; color: #fff; border: none; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#1d4ed8'" onmouseout="this.style.background='#2563eb'">
                        <i class="fa-solid fa-pen-nib"></i> Assinar
                    </button>
                </div>
            </div>

            <!-- Linha 2: ETP -->
            <div class="document-row" style="display: flex; justify-content: space-between; align-items: center; padding: 1.25rem; border-radius: 12px; transition: all 0.2s;">
                <div>
                    <h4 style="margin: 0; font-size: 1rem; font-weight: 500;">Estudo Técnico Preliminar (ETP)</h4>
                    <p style="margin: 0.2rem 0; font-size: 0.85rem;">Análise de viabilidade e levantamento de soluções.</p>
                </div>
                <div style="display: flex; gap: 0.75rem;">
                    <a id="btn-download-etp" href="#" class="btn btn-secondary" style="padding: 0.6rem 1.25rem; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 0.5rem; border-radius: 8px; text-decoration: none; transition: all 0.2s;">
                        <i class="fa-solid fa-download"></i> Download
                    </a>
                    <button id="btn-sign-etp" class="btn btn-primary btn-sign" style="padding: 0.6rem 1.25rem; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 0.5rem; border-radius: 8px; background: #2563eb; color: #fff; border: none; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#1d4ed8'" onmouseout="this.style.background='#2563eb'">
                        <i class="fa-solid fa-pen-nib"></i> Assinar
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Botão de Encaminhar para o Gabinete -->
        <div id="forward-container" style="margin-top: 1.5rem; text-align: right; display: none;">
            <button id="btn-forward-gabinete" class="btn btn-success" style="padding: 0.75rem 1.5rem; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 0.5rem; border-radius: 8px; background: #059669; color: #fff; border: none; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#047857'" onmouseout="this.style.background='#059669'">
                <i class="fa-solid fa-paper-plane"></i> Encaminhar para o Gabinete
            </button>
        </div>
    </div>
</div>

<script>
    function openModal(id, code, title, canSign, sdStatus, etpStatus) {
        document.getElementById('modal-req-code').innerText = code;
        document.getElementById('modal-req-title').innerText = title;
        
        // Update download links
        document.getElementById('btn-download-sd').href = `/planejamento/modulo-1/${id}/sd`;
        document.getElementById('btn-download-etp').href = `/planejamento/modulo-1/${id}/etp`;
        
        // Update SD row
        const btnSignSd = document.getElementById('btn-sign-sd');
        if (sdStatus === 'concluido') {
            btnSignSd.innerHTML = '<i class="fa-solid fa-check"></i> Assinado';
            btnSignSd.style.background = '#059669';
            btnSignSd.disabled = true;
            btnSignSd.style.cursor = 'default';
        } else if (sdStatus === 'pendente') {
            btnSignSd.innerHTML = '<i class="fa-solid fa-arrows-counter-clockwise"></i> Verificar';
            btnSignSd.style.background = '#f59e0b';
            btnSignSd.disabled = false;
            btnSignSd.style.cursor = 'pointer';
            btnSignSd.onclick = () => verifySignature(id);
        } else {
            btnSignSd.innerHTML = '<i class="fa-solid fa-pen-nib"></i> Assinar';
            btnSignSd.style.background = '#2563eb';
            btnSignSd.disabled = false;
            btnSignSd.style.cursor = 'pointer';
            btnSignSd.onclick = () => initializeSignature(id, 'sd');
        }
        
        // Update ETP row
        const btnSignEtp = document.getElementById('btn-sign-etp');
        if (etpStatus === 'concluido') {
            btnSignEtp.innerHTML = '<i class="fa-solid fa-check"></i> Assinado';
            btnSignEtp.style.background = '#059669';
            btnSignEtp.disabled = true;
            btnSignEtp.style.cursor = 'default';
        } else if (etpStatus === 'pendente') {
            btnSignEtp.innerHTML = '<i class="fa-solid fa-arrows-counter-clockwise"></i> Verificar';
            btnSignEtp.style.background = '#f59e0b';
            btnSignEtp.disabled = false;
            btnSignEtp.style.cursor = 'pointer';
            btnSignEtp.onclick = () => verifySignature(id);
        } else {
            btnSignEtp.innerHTML = '<i class="fa-solid fa-pen-nib"></i> Assinar';
            btnSignEtp.style.background = '#2563eb';
            btnSignEtp.disabled = false;
            btnSignEtp.style.cursor = 'pointer';
            btnSignEtp.onclick = () => initializeSignature(id, 'etp');
        }
        
        // Hide buttons if canSign is false
        if (canSign !== 'true') {
            btnSignSd.style.display = 'none';
            btnSignEtp.style.display = 'none';
        } else {
            btnSignSd.style.display = 'inline-flex';
            btnSignEtp.style.display = 'inline-flex';
        }
        
        // Show forward button if both are signed
        const forwardContainer = document.getElementById('forward-container');
        const btnForward = document.getElementById('btn-forward-gabinete');
        
        if (sdStatus === 'concluido' && etpStatus === 'concluido') {
            forwardContainer.style.display = 'block';
            btnForward.onclick = () => forwardToGabinete(id);
        } else {
            forwardContainer.style.display = 'none';
        }
        
        document.getElementById('document-modal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('document-modal').style.display = 'none';
    }

    async function initializeSignature(id, type) {
        if (!confirm('Deseja iniciar o processo de assinatura para esta demanda?')) return;
        
        const newTab = window.open('about:blank', '_blank');
        if (!newTab) {
            alert('Por favor, permita pop-ups para este site.');
            return;
        }

        try {
            const response = await fetch(`/planejamento/modulo-1/${id}/signature/initialize?type=${type}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            
            const data = await response.json();

            if (data.success && data.sign_url) {
                newTab.location.href = data.sign_url;
                alert('Processo de assinatura iniciado. Verifique a nova aba.');
                window.location.reload();
            } else {
                newTab.close();
                alert(data.message || 'Falha ao iniciar processo de assinatura.');
            }
        } catch (e) {
            newTab.close();
            console.error('Error starting signature:', e);
            alert('Ocorreu um erro ao contatar o servidor de assinaturas.');
        }
    }

    async function verifySignature(id) {
        try {
            const response = await fetch(`/planejamento/modulo-1/${id}/signature/verify`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            
            const data = await response.json();

            if (data.success) {
                alert(data.message || 'Assinatura verificada com sucesso!');
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    window.location.reload();
                }
            } else {
                if (data.sign_url) {
                    if (confirm(data.message + '\n\nDeseja abrir a página de assinatura novamente?')) {
                        window.open(data.sign_url, '_blank');
                    }
                } else {
                    alert(data.message || 'Assinatura ainda pendente.');
                }
            }
        } catch (e) {
            console.error('Error verifying signature:', e);
            alert('Ocorreu um erro ao contatar o servidor.');
        }
    }

    async function forwardToGabinete(id) {
        if (!confirm('Tem certeza que deseja encaminhar esta demanda para o Gabinete?')) return;
        
        try {
            const response = await fetch(`/planejamento/modulo-1/${id}/signature/forward`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            
            const data = await response.json();

            if (data.success) {
                alert(data.message || 'Demanda encaminhada com sucesso!');
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    window.location.reload();
                }
            } else {
                alert(data.message || 'Falha ao encaminhar demanda.');
            }
        } catch (e) {
            console.error('Error forwarding to gabinete:', e);
            alert('Ocorreu um erro ao contatar o servidor.');
        }
    }

    // Add event listeners to view buttons
    document.addEventListener('DOMContentLoaded', () => {
        const viewButtons = document.querySelectorAll('.btn-view-request');
        viewButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const id = btn.getAttribute('data-id');
                const code = btn.getAttribute('data-code');
                const title = btn.getAttribute('data-title');
                const canSign = btn.getAttribute('data-cansign');
                const sdStatus = btn.getAttribute('data-sd-status');
                const etpStatus = btn.getAttribute('data-etp-status');
                openModal(id, code, title, canSign, sdStatus, etpStatus);
            });
        });
        
        // Close modal on outside click
        const modal = document.getElementById('document-modal');
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });
    });
</script>
@endsection
