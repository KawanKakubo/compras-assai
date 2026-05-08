@extends('layouts.dashboard')

@section('title', 'Cacheamento Geométrico')
@section('header_title', 'Cacheamento Geométrico')
@section('header_subtitle', 'Gerencie e sincronize o cache local do catálogo do Governo Federal (CATMAT/CATSER) para máxima resiliência.')

@section('content')
@if (session('success'))
    <div style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; border: 1px solid rgba(16, 185, 129, 0.2); display: flex; align-items: center; gap: 10px;">
        <i class="fa-solid fa-circle-check" style="font-size: 1.2rem;"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif

@if (session('error'))
    <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; border: 1px solid rgba(239, 68, 68, 0.2); display: flex; align-items: center; gap: 10px;">
        <i class="fa-solid fa-circle-exclamation" style="font-size: 1.2rem;"></i>
        <span>{{ session('error') }}</span>
    </div>
@endif

<!-- Stats Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <!-- Materials Cached -->
    <div class="card" style="position: relative; overflow: hidden; border-left: 4px solid var(--primary);">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <p style="font-size: 0.9rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Materiais (CATMAT)</p>
                <h2 id="materials-count-val" style="font-size: 2.2rem; font-weight: 800; margin: 0.5rem 0; color: var(--text-main);">{{ number_format($materialsCount, 0, ',', '.') }}</h2>
                <p style="font-size: 0.8rem; color: var(--text-muted);"><i class="fa-solid fa-circle-check" style="color: #10b981; margin-right: 4px;"></i> Itens em cache local</p>
            </div>
            <div style="width: 60px; height: 60px; border-radius: 15px; background: rgba(0, 97, 255, 0.1); display: flex; align-items: center; justify-content: center; color: var(--primary);">
                <i class="fa-solid fa-boxes-stacked" style="font-size: 1.8rem;"></i>
            </div>
        </div>
    </div>

    <!-- Services Cached -->
    <div class="card" style="position: relative; overflow: hidden; border-left: 4px solid var(--secondary);">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <p style="font-size: 0.9rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Serviços (CATSER)</p>
                <h2 id="services-count-val" style="font-size: 2.2rem; font-weight: 800; margin: 0.5rem 0; color: var(--text-main);">{{ number_format($servicesCount, 0, ',', '.') }}</h2>
                <p style="font-size: 0.8rem; color: var(--text-muted);"><i class="fa-solid fa-circle-check" style="color: #10b981; margin-right: 4px;"></i> Itens em cache local</p>
            </div>
            <div style="width: 60px; height: 60px; border-radius: 15px; background: rgba(236, 72, 153, 0.1); display: flex; align-items: center; justify-content: center; color: var(--secondary);">
                <i class="fa-solid fa-screwdriver-wrench" style="font-size: 1.8rem;"></i>
            </div>
        </div>
    </div>

    <!-- Taxonomy Cached -->
    <div class="card" style="position: relative; overflow: hidden; border-left: 4px solid #10b981;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <p style="font-size: 0.9rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Nós de Taxonomia</p>
                <h2 id="taxonomy-count-val" style="font-size: 2.2rem; font-weight: 800; margin: 0.5rem 0; color: var(--text-main);">{{ number_format($taxonomyCount, 0, ',', '.') }}</h2>
                <p style="font-size: 0.8rem; color: var(--text-muted);"><i class="fa-solid fa-network-wired" style="color: #10b981; margin-right: 4px;"></i> Grupos/Classes cached</p>
            </div>
            <div style="width: 60px; height: 60px; border-radius: 15px; background: rgba(16, 185, 129, 0.1); display: flex; align-items: center; justify-content: center; color: #10b981;">
                <i class="fa-solid fa-sitemap" style="font-size: 1.8rem;"></i>
            </div>
        </div>
    </div>
</div>

<!-- Caching Controller Panel -->
<div class="card" id="mainCachePanel" style="background: linear-gradient(135deg, rgba(37, 99, 235, 0.05) 0%, rgba(236, 72, 153, 0.05) 100%); border: 1px solid var(--border); border-radius: 20px; padding: 2.5rem; margin-bottom: 2rem;">
    <div style="max-width: 800px; margin: 0 auto; text-align: center;">
        <div style="display: inline-block; width: 80px; height: 80px; border-radius: 50%; background: rgba(37, 99, 235, 0.1); display: flex; align-items: center; justify-content: center; color: var(--primary); margin: 0 auto 1.5rem auto; box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.3);">
            <i class="fa-solid fa-cloud-arrow-down" style="font-size: 2.5rem; animation: pulse 2s infinite;"></i>
        </div>
        <h2 style="font-size: 1.8rem; font-weight: 700; color: var(--text-main); margin-bottom: 1rem;">Sincronismo Geométrico de Dados Abertos</h2>
        <p style="color: var(--text-muted); line-height: 1.6; font-size: 1rem; margin-bottom: 2rem;">
            A Compras.gov.br API frequentemente passa por instabilidades ou erros de JPA EntityManager. O nosso cache geométrico
            prioriza buscas locais no banco de dados do município de Assaí, realizando a busca federal apenas caso o item não seja localizado.
            Clique no botão abaixo para baixar a base estruturada aberta completa do CATMAT/CATSER e alimentar o banco de dados.
        </p>

        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <form action="{{ route('admin.cache-geometrico.sync') }}" method="POST" id="syncForm">
                @csrf
                <button type="submit" class="btn btn-primary" style="padding: 1rem 2rem; font-size: 1rem; font-weight: 600; border-radius: 12px; gap: 10px; box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.4);">
                    <i class="fa-solid fa-cloud-arrow-down"></i> Baixar Base Estruturada
                </button>
            </form>

            <form action="{{ route('admin.cache-geometrico.clear') }}" method="POST" onsubmit="return confirm('ATENÇÃO: Isso excluirá todos os itens do cache local! Deseja continuar?')">
                @csrf
                <button type="submit" class="btn" style="padding: 1rem 2rem; font-size: 1rem; font-weight: 600; border-radius: 12px; background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); transition: var(--transition);">
                    <i class="fa-solid fa-trash-can"></i> Limpar Cache Local
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Upload Catalog Files Card -->
<div class="card" id="uploadCatalogPanel" style="border: 1px solid var(--border); border-radius: 20px; padding: 2.5rem; margin-bottom: 2rem;">
    <div style="max-width: 800px; margin: 0 auto;">
        <h3 style="font-size: 1.4rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-file-excel" style="color: #10b981;"></i>
            <span>Importar Planilhas de Catálogo Offline</span>
        </h3>
        <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 1.5rem; line-height: 1.5;">
            Se você possui os arquivos completos extraídos do CATMAT ou CATSER (formato Excel `.xlsx`), pode fazer o upload deles aqui.
            O processamento será executado de forma assíncrona e em lote para garantir máxima estabilidade e velocidade.
        </p>

        <form id="uploadCatalogForm" enctype="multipart/form-data">
            @csrf
            <!-- Select Type Pills -->
            <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; align-items: center; justify-content: center; background: rgba(0,0,0,0.02); padding: 0.5rem; border-radius: 12px; width: fit-content; margin: 0 auto 1.5rem auto;">
                <label style="font-weight: 600; font-size: 0.9rem; color: var(--text-muted); margin-right: 0.5rem; margin-left: 0.5rem;">Tipo de Planilha:</label>
                
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 0.5rem 1rem; border-radius: 8px; background: rgba(37, 99, 235, 0.05); color: var(--primary); font-weight: 700; border: 1px solid rgba(37, 99, 235, 0.1);">
                    <input type="radio" name="catalog_type" value="material" checked style="accent-color: var(--primary);">
                    <i class="fa-solid fa-boxes-stacked"></i> Materiais (CATMAT)
                </label>

                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 0.5rem 1rem; border-radius: 8px; background: rgba(236, 72, 153, 0.05); color: var(--secondary); font-weight: 700; border: 1px solid rgba(236, 72, 153, 0.1);">
                    <input type="radio" name="catalog_type" value="service" style="accent-color: var(--secondary);">
                    <i class="fa-solid fa-screwdriver-wrench"></i> Serviços (CATSER)
                </label>
            </div>

            <!-- Drag and Drop Area -->
            <div id="dropZone" style="border: 2px dashed var(--border); border-radius: 15px; padding: 3rem 2rem; text-align: center; background: rgba(0,0,0,0.01); transition: all 0.3s ease; cursor: pointer; position: relative;">
                <input type="file" id="catalogFile" name="file" accept=".xlsx, .xls" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer;">
                <div style="font-size: 3.2rem; color: var(--border); margin-bottom: 1rem;" id="dropZoneIcon">
                    <i class="fa-solid fa-cloud-arrow-up" style="transition: transform 0.3s ease;"></i>
                </div>
                <h4 style="font-size: 1.1rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.25rem;">Arraste e solte o arquivo aqui</h4>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">ou clique para navegar no seu computador</p>
                <div id="selectedFileInfo" style="display: none; background: rgba(16, 185, 129, 0.05); border: 1px dashed rgba(16, 185, 129, 0.3); padding: 0.75rem 1.5rem; border-radius: 10px; color: #10b981; font-weight: 600; width: fit-content; margin: 0 auto; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-file-excel"></i>
                    <span id="selectedFileName">planilha.xlsx</span>
                </div>
            </div>

            <!-- Action button -->
            <div style="text-align: center; margin-top: 1.5rem;">
                <button type="submit" id="startImportBtn" class="btn btn-primary" style="padding: 0.9rem 2.5rem; border-radius: 12px; font-weight: 700; font-size: 0.95rem; box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.2);" disabled>
                    <i class="fa-solid fa-file-import"></i> Enviar e Processar Planilha
                </button>
            </div>
        </form>

        <!-- Local upload progress indicator -->
        <div id="uploadProgressIndicator" style="display: none; margin-top: 1.5rem; text-align: center;">
            <p style="font-weight: 600; font-size: 0.95rem; color: var(--text-main); margin-bottom: 0.5rem; display: flex; align-items: center; justify-content: center; gap: 8px;">
                <i class="fa-solid fa-spinner fa-spin" style="color: var(--primary);"></i>
                <span id="uploadProgressText">Enviando planilha para o servidor...</span>
            </p>
            <div style="width: 100%; height: 8px; background: rgba(0,0,0,0.08); border-radius: 5px; overflow: hidden; max-width: 400px; margin: 0 auto;">
                <div id="uploadProgressBarFill" style="width: 0%; height: 100%; background: var(--primary); border-radius: 5px; transition: width 0.1s ease-out;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Progress Panel (Hidden initially) -->
<div class="card" id="progressPanel" style="display: none; border: 1px solid var(--border); border-radius: 20px; padding: 2.5rem; margin-bottom: 2rem; background: rgba(15, 23, 42, 0.02);">
    <div style="max-width: 800px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <div>
                <h3 style="font-weight: 700; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-spinner fa-spin" style="color: var(--primary);" id="progressSpinner"></i>
                    <span id="progressTitle">Sincronizando Base de Dados Abertos (ETL)...</span>
                </h3>
                <p id="progressStatusText" style="font-size: 0.9rem; color: var(--text-muted); margin-top: 0.25rem;">Iniciando processo...</p>
            </div>
            <span id="progressPercent" style="font-size: 1.5rem; font-weight: 800; color: var(--primary);">0%</span>
        </div>

        <!-- Progress Bar Track -->
        <div style="width: 100%; height: 12px; background: rgba(0,0,0,0.08); border-radius: 10px; overflow: hidden; margin-bottom: 1.5rem; border: 1px solid rgba(0,0,0,0.02);">
            <div id="progressBarFill" style="width: 0%; height: 100%; background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%); border-radius: 10px; transition: width 0.4s ease-out;"></div>
        </div>

        <!-- Mini Stats -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem; text-align: center;">
            <div style="background: rgba(37, 99, 235, 0.05); padding: 0.75rem; border-radius: 10px;">
                <span style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Materiais em cache local</span>
                <h4 id="progressMaterialsCount" style="font-size: 1.25rem; font-weight: 700; color: var(--text-main); margin: 0.25rem 0 0 0;">0</h4>
            </div>
            <div style="background: rgba(236, 72, 153, 0.05); padding: 0.75rem; border-radius: 10px;">
                <span style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Serviços em cache local</span>
                <h4 id="progressServicesCount" style="font-size: 1.25rem; font-weight: 700; color: var(--text-main); margin: 0.25rem 0 0 0;">0</h4>
            </div>
        </div>

        <!-- Live Terminal Log Console -->
        <div style="margin-bottom: 1.5rem;">
            <label style="font-size: 0.85rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.5rem; display: block;">Console de Logs do Processo</label>
            <div id="logConsole" style="background: #0f172a; color: #38bdf8; font-family: monospace; font-size: 0.85rem; padding: 1.2rem; border-radius: 12px; height: 180px; overflow-y: auto; box-shadow: inset 0 2px 4px rgba(0,0,0,0.6); border: 1px solid rgba(255,255,255,0.1); line-height: 1.6;">
                <div style="color: #64748b;">[CONSOLE_INIT] Conectando ao barramento de eventos de ETL...</div>
            </div>
        </div>

        <div style="text-align: right; display: none;" id="progressFinishBtnContainer">
            <button class="btn btn-primary" onclick="window.location.reload()" style="padding: 0.6rem 1.5rem; border-radius: 8px;">Concluir e Atualizar</button>
        </div>
    </div>
</div>

<!-- Preview lists -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2rem;">
    <!-- Materials Cache Preview -->
    <div class="card">
        <h3 style="display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-boxes-stacked" style="color: var(--primary);"></i>
            <span>Materiais Recentes (CATMAT)</span>
        </h3>
        <br>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 100px;">Código</th>
                        <th>Descrição do Item</th>
                    </tr>
                </thead>
                <tbody id="materialsPreviewBody">
                    @forelse($sampleItems as $item)
                    <tr>
                        <td style="font-family: monospace; font-weight: 700;">{{ $item->item_code }}</td>
                        <td>{{ $item->description }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="2" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                            Nenhum material em cache local.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Services Cache Preview -->
    <div class="card">
        <h3 style="display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-screwdriver-wrench" style="color: var(--secondary);"></i>
            <span>Serviços Recentes (CATSER)</span>
        </h3>
        <br>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 100px;">Código</th>
                        <th>Descrição do Serviço</th>
                    </tr>
                </thead>
                <tbody id="servicesPreviewBody">
                    @forelse($sampleServices as $service)
                    <tr>
                        <td style="font-family: monospace; font-weight: 700;">{{ $service->service_code }}</td>
                        <td>{{ $service->description }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="2" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                            Nenhum serviço em cache local.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}
</style>
@endsection

@section('scripts')
<script>
    const mainCachePanel = document.getElementById('mainCachePanel');
    const progressPanel = document.getElementById('progressPanel');
    const progressPercent = document.getElementById('progressPercent');
    const progressBarFill = document.getElementById('progressBarFill');
    const progressStatusText = document.getElementById('progressStatusText');
    const progressMaterialsCount = document.getElementById('progressMaterialsCount');
    const progressServicesCount = document.getElementById('progressServicesCount');
    const logConsole = document.getElementById('logConsole');
    const progressSpinner = document.getElementById('progressSpinner');
    const progressTitle = document.getElementById('progressTitle');
    const progressFinishBtnContainer = document.getElementById('progressFinishBtnContainer');

    const materialsCountVal = document.getElementById('materials-count-val');
    const servicesCountVal = document.getElementById('services-count-val');
    const taxonomyCountVal = document.getElementById('taxonomy-count-val');

    let pollInterval = null;

    // Check if progress is already running on page load
    window.addEventListener('DOMContentLoaded', () => {
        fetch("{{ route('admin.cache-geometrico.progress') }}")
        .then(response => response.json())
        .then(data => {
            if (data.status === 'processing') {
                mainCachePanel.style.display = 'none';
                if (document.getElementById('uploadCatalogPanel')) {
                    document.getElementById('uploadCatalogPanel').style.display = 'none';
                }
                progressPanel.style.display = 'block';
                startPolling();
            }
        });
    });

    document.getElementById('syncForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const btn = this.querySelector('button');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Inicializando ETL...';

        // Post request via fetch to start the ETL
        fetch("{{ route('admin.cache-geometrico.sync') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Show progress bar & start polling progress
                mainCachePanel.style.display = 'none';
                if (document.getElementById('uploadCatalogPanel')) {
                    document.getElementById('uploadCatalogPanel').style.display = 'none';
                }
                progressPanel.style.display = 'block';
                startPolling();
            } else {
                alert(data.message || 'Erro ao iniciar o ETL.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-cloud-arrow-down"></i> Baixar Base Estruturada';
            }
        })
        .catch(err => {
            console.error(err);
            alert('Falha na comunicação com o servidor.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-cloud-arrow-down"></i> Baixar Base Estruturada';
        });
    });

    function startPolling() {
        if (pollInterval) clearInterval(pollInterval);
        pollInterval = setInterval(fetchProgress, 1000);
    }

    function fetchProgress() {
        fetch("{{ route('admin.cache-geometrico.progress') }}")
        .then(response => response.json())
        .then(data => {
            // Update progress percent and bar
            const percent = data.progress || 0;
            progressPercent.innerText = percent + '%';
            progressBarFill.style.width = percent + '%';
            
            // Update status text and counters
            progressStatusText.innerText = data.message || 'Processando...';
            progressMaterialsCount.innerText = formatNumber(data.processed_materials || 0);
            progressServicesCount.innerText = formatNumber(data.processed_services || 0);

            // Update real-time counts at the top cards
            if (materialsCountVal) materialsCountVal.innerText = formatNumber(data.materials_count || 0);
            if (servicesCountVal) servicesCountVal.innerText = formatNumber(data.services_count || 0);
            if (taxonomyCountVal) taxonomyCountVal.innerText = formatNumber(data.taxonomy_count || 0);

            // Append logs
            if (data.logs && data.logs.length > 0) {
                logConsole.innerHTML = data.logs.map(log => {
                    let color = '#38bdf8';
                    if (log.includes('[ERROR]') || log.includes('ERROR')) color = '#f87171';
                    else if (log.includes('[CATMAT]') || log.includes('CATMAT') || log.includes('material')) color = '#38bdf8';
                    else if (log.includes('[CATSER]') || log.includes('CATSER') || log.includes('serviço')) color = '#f472b6';
                    return `<div style="margin-bottom: 4px; border-left: 2px solid ${color}; padding-left: 6px; color: #f8fafc;">${escapeHtml(log)}</div>`;
                }).join('');
                
                // Auto-scroll to bottom of terminal
                logConsole.scrollTop = logConsole.scrollHeight;
            }

            // Check if finished
            if (data.status === 'completed') {
                clearInterval(pollInterval);
                progressSpinner.className = 'fa-solid fa-circle-check';
                progressSpinner.style.color = '#10b981';
                progressSpinner.style.animation = 'none';
                progressTitle.innerText = 'Sincronização Concluída com Sucesso!';
                progressStatusText.innerText = 'O banco de dados foi alimentado com a base estruturada aberta.';
                progressFinishBtnContainer.style.display = 'block';
            } else if (data.status === 'error') {
                clearInterval(pollInterval);
                progressSpinner.className = 'fa-solid fa-circle-xmark';
                progressSpinner.style.color = '#ef4444';
                progressSpinner.style.animation = 'none';
                progressTitle.innerText = 'Erro na Sincronização';
                progressStatusText.innerText = data.message || 'Ocorreu um erro no processamento do ETL.';
                progressFinishBtnContainer.style.display = 'block';
            }
        })
        .catch(err => {
            console.error('Error fetching progress:', err);
        });
    }

    function formatNumber(num) {
        return new Intl.NumberFormat('pt-BR').format(num);
    }

    function escapeHtml(text) {
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Drag and Drop File Upload Logic
    const dropZone = document.getElementById('dropZone');
    const catalogFileInput = document.getElementById('catalogFile');
    const selectedFileInfo = document.getElementById('selectedFileInfo');
    const selectedFileName = document.getElementById('selectedFileName');
    const startImportBtn = document.getElementById('startImportBtn');
    const dropZoneIcon = document.getElementById('dropZoneIcon');

    if (catalogFileInput) {
        catalogFileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                showSelectedFile(this.files[0]);
            }
        });
    }

    function showSelectedFile(file) {
        selectedFileName.innerText = file.name + ' (' + formatBytes(file.size) + ')';
        selectedFileInfo.style.setProperty('display', 'flex', 'important');
        selectedFileInfo.style.display = 'flex';
        startImportBtn.disabled = false;
        dropZoneIcon.style.color = '#10b981';
        dropZoneIcon.querySelector('i').className = 'fa-solid fa-circle-check';
    }

    function formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }

    if (dropZone) {
        // Drag & drop visual effects
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, (e) => {
                e.preventDefault();
                dropZone.style.background = 'rgba(37, 99, 235, 0.03)';
                dropZone.style.borderColor = 'var(--primary)';
                dropZone.querySelector('i').style.transform = 'scale(1.1)';
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, (e) => {
                e.preventDefault();
                dropZone.style.background = 'rgba(0,0,0,0.01)';
                dropZone.style.borderColor = 'var(--border)';
                dropZone.querySelector('i').style.transform = 'scale(1)';
            }, false);
        });

        dropZone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files && files[0]) {
                catalogFileInput.files = files;
                showSelectedFile(files[0]);
            }
        });
    }

    const uploadCatalogForm = document.getElementById('uploadCatalogForm');
    if (uploadCatalogForm) {
        uploadCatalogForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const file = catalogFileInput.files[0];
            const type = document.querySelector('input[name="catalog_type"]:checked').value;
            
            if (!file) return;

            // Disable buttons & show uploader indicator
            startImportBtn.disabled = true;
            catalogFileInput.disabled = true;
            document.getElementById('uploadProgressIndicator').style.display = 'block';
            
            const formData = new FormData();
            formData.append('file', file);
            formData.append('type', type);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', "{{ route('admin.cache-geometrico.upload') }}", true);
            xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');
            xhr.setRequestHeader('Accept', 'application/json');

            // Track upload progress
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percentComplete = Math.round((e.loaded / e.total) * 100);
                    document.getElementById('uploadProgressBarFill').style.width = percentComplete + '%';
                    document.getElementById('uploadProgressText').innerText = `Enviando planilha: ${percentComplete}% (${formatBytes(e.loaded)} de ${formatBytes(e.total)})`;
                }
            });

            xhr.onload = function() {
                if (xhr.status === 200) {
                    const res = JSON.parse(xhr.responseText);
                    if (res.status === 'success') {
                        // Swap views to progress panels and begin polling status
                        document.getElementById('uploadCatalogPanel').style.display = 'none';
                        mainCachePanel.style.display = 'none';
                        progressPanel.style.display = 'block';
                        startPolling();
                    } else {
                        alert(res.message || 'Erro durante o upload.');
                        resetUploadForm();
                    }
                } else {
                    let errorMsg = 'Erro na comunicação com o servidor.';
                    try {
                        const res = JSON.parse(xhr.responseText);
                        errorMsg = res.message || errorMsg;
                    } catch(err) {}
                    alert(errorMsg);
                    resetUploadForm();
                }
            };

            xhr.onerror = function() {
                alert('Erro de conexão ao enviar planilha.');
                resetUploadForm();
            };

            xhr.send(formData);
        });
    }

    function resetUploadForm() {
        startImportBtn.disabled = false;
        catalogFileInput.disabled = false;
        catalogFileInput.value = '';
        selectedFileInfo.style.setProperty('display', 'none', 'important');
        selectedFileInfo.style.display = 'none';
        document.getElementById('uploadProgressIndicator').style.display = 'none';
        document.getElementById('uploadProgressBarFill').style.width = '0%';
        dropZoneIcon.style.color = 'var(--border)';
        dropZoneIcon.querySelector('i').className = 'fa-solid fa-cloud-arrow-up';
    }
</script>
@endsection
