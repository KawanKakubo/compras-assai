@extends('layouts.dashboard')

@section('title', 'Processando Assinatura')
@section('header_title', 'Assinatura Digital')
@section('header_subtitle', 'Sua via está sendo processada e registrada criptograficamente...')

@section('content')
<div style="max-width: 600px; margin: 4rem auto; text-align: center;">
    <div class="card" style="padding: 3rem 2rem; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); background: var(--card-bg); border: 1px solid var(--border);">
        
        <!-- Loading Animation / Icon -->
        <div class="loader-container" style="margin-bottom: 2rem; position: relative; display: inline-block;">
            <div class="spinner" style="width: 80px; height: 80px; border: 5px solid rgba(37, 99, 235, 0.1); border-left-color: #2563eb; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto;"></div>
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #2563eb; font-size: 2rem;">
                <i class="ph ph-signature"></i>
            </div>
        </div>

        <h2 id="status-title" style="font-weight: 600; margin-bottom: 1rem; color: var(--text-main);">Processando Certificado...</h2>
        
        @if($isBypass)
            <div style="display: inline-block; background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 0.3rem 1rem; border-radius: 50px; font-size: 0.8rem; font-weight: bold; margin-bottom: 1.5rem; border: 1px solid rgba(16, 185, 129, 0.2);">
                <i class="ph ph-info" style="vertical-align: middle;"></i> ASSINADOR BYPASS ATIVO
            </div>
        @endif

        <p id="status-desc" style="color: var(--text-muted); font-size: 1.05rem; line-height: 1.6; margin-bottom: 2rem;">
            Aguardando validação dos metadados e selo de conformidade da API do Nextcloud LibreSign. Por favor, não feche esta página...
        </p>

        <!-- Hidden Verification Form -->
        <div id="action-buttons" style="display: none; gap: 1rem; justify-content: center;">
            <button onclick="verifyNow()" class="btn btn-primary" style="padding: 0.8rem 2rem;">
                <i class="ph ph-arrows-counter-clockwise"></i> Verificar Novamente
            </button>
            <a href="{{ route('planning.module-one.show', $procurementRequest) }}" class="btn" style="padding: 0.8rem 2rem; background: rgba(148, 163, 184, 0.1); color: #94a3b8; border: 1px solid rgba(148, 163, 184, 0.2);">
                Voltar à Demanda
            </a>
        </div>
    </div>
</div>

<style>
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto trigger verification after 1.5 seconds to give the server a tiny breather
        setTimeout(verifyNow, 1500);
    });

    function verifyNow() {
        const titleEl = document.getElementById('status-title');
        const descEl = document.getElementById('status-desc');
        const buttonsEl = document.getElementById('action-buttons');
        const spinner = document.querySelector('.spinner');

        titleEl.textContent = "Verificando Assinatura...";
        descEl.textContent = "Consultando os servidores do Assina Assaí e baixando o arquivo final com chaves criptográficas...";
        buttonsEl.style.display = 'none';
        if (spinner) spinner.style.animationPlayState = 'running';

        fetch("{{ route('planning.signature.verify', $procurementRequest) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                titleEl.textContent = "Assinado com Sucesso!";
                descEl.innerHTML = `<span style="color: #10b981; font-weight: bold;">✔ Assinatura digital registrada.</span><br>Redirecionando de volta para a sua demanda...`;
                if (spinner) spinner.style.animationPlayState = 'paused';
                
                // Show celebration/success behavior
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1500);
            } else {
                titleEl.textContent = "Assinatura Pendente";
                descEl.textContent = data.message || "Não conseguimos confirmar a conclusão da assinatura ainda. Certifique-se de que assinou na guia do LibreSign.";
                buttonsEl.style.display = 'flex';
                if (spinner) spinner.style.animationPlayState = 'paused';
            }
        })
        .catch(error => {
            console.error('Error verifying signature:', error);
            titleEl.textContent = "Ocorreu um Erro";
            descEl.textContent = "Não foi possível estabelecer contato com a API de verificação. Tente novamente mais tarde.";
            buttonsEl.style.display = 'flex';
            if (spinner) spinner.style.animationPlayState = 'paused';
        });
    }
</script>
@endsection
