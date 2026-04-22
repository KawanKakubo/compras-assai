document.addEventListener('DOMContentLoaded', () => {
    // ── Elements ─────────────────────────────────────────────────────────────
    const form = document.getElementById('wizard-form');
    const steps = Array.from(document.querySelectorAll('.wizard-step'));
    const progressSteps = Array.from(document.querySelectorAll('.progress-step'));
    const btnPrev = document.getElementById('btn-prev');
    const btnNext = document.getElementById('btn-next');
    const btnSubmit = document.getElementById('btn-submit');
    const btnAddItem = document.getElementById('btn-add-item');
    const itemsContainer = document.getElementById('items-container');
    
    // Data placeholders
    const thresholds = window.WIZARD_DATA?.thresholds || { inciso_i: 130984.20, inciso_ii: 65492.11 };
    
    let currentStep = 0;
    
    // ── Navigation ──────────────────────────────────────────────────────────
    function updateWizard() {
        // Show/hide steps
        steps.forEach((step, index) => {
            if (index === currentStep) {
                step.classList.add('active');
            } else {
                step.classList.remove('active');
            }
        });
        
        // Update progress bar
        progressSteps.forEach((progress, index) => {
            progress.classList.remove('active', 'completed');
            if (index === currentStep) {
                progress.classList.add('active');
            } else if (index < currentStep) {
                progress.classList.add('completed');
            }
        });
        
        // Buttons state
        if (currentStep === 0) {
            btnPrev.style.display = 'none';
        } else {
            btnPrev.style.display = 'inline-flex';
        }
        
        if (currentStep === steps.length - 1) {
            btnNext.style.display = 'none';
            btnSubmit.style.display = 'inline-flex';
            populateReviewStep(); // Prepare review data when reaching the last step
        } else {
            btnNext.style.display = 'inline-flex';
            btnSubmit.style.display = 'none';
        }
    }
    
    function validateStep(stepIndex) {
        // Basic HTML5 validation for the active step
        const step = steps[stepIndex];
        const inputs = step.querySelectorAll('input[required], select[required], textarea[required]');
        let isValid = true;
        
        inputs.forEach(input => {
            // Ignore hidden inputs (due to conditional logic)
            if (input.closest('.conditional') && !input.closest('.conditional').classList.contains('show')) {
                return;
            }
            if (!input.checkValidity()) {
                input.reportValidity();
                isValid = false;
            }
        });
        
        // Custom validations
        if (stepIndex === 2) { // Items step
            const itemCards = itemsContainer.querySelectorAll('.item-card');
            if (itemCards.length === 0) {
                alert('Adicione pelo menos um item à demanda.');
                isValid = false;
            }
        }
        
        return isValid;
    }
    
    btnNext.addEventListener('click', () => {
        if (validateStep(currentStep)) {
            // Trigger specific step actions
            if (currentStep === 2) {
                calculateTotalsAndFraming();
            }
            
            currentStep++;
            updateWizard();
            window.scrollTo(0, 0);
        }
    });
    
    btnPrev.addEventListener('click', () => {
        currentStep--;
        updateWizard();
        window.scrollTo(0, 0);
    });
    
    // ── Conditional Logic ────────────────────────────────────────────────────
    function setupConditionals() {
        const toggleConditionals = [
            { trigger: '#priority_level', target: '#conditional-priority', value: 'high', isSelect: true },
            { trigger: 'input[name="has_environmental_impact"]', target: '#conditional-environmental' },
            { trigger: 'input[name="has_reverse_logistics"]', target: '#conditional-logistics' },
            { trigger: 'input[name="municipal_policy_applies"]', target: '#conditional-municipal-policy' },
            { trigger: 'input[name="study[is_in_pca]"]', target: '#conditional-pca' },
            { trigger: 'input[name="study[municipal_program_eligible]"]', target: '#conditional-municipal-program' }
        ];
        
        toggleConditionals.forEach(cond => {
            const triggerEl = document.querySelector(cond.trigger);
            if (!triggerEl) return;
            
            const targetEl = document.querySelector(cond.target);
            if (!targetEl) return;
            
            if (cond.isSelect) {
                triggerEl.addEventListener('change', (e) => {
                    if (e.target.value === cond.value) {
                        targetEl.classList.add('show');
                        targetEl.querySelectorAll('input, textarea').forEach(i => i.setAttribute('required', 'required'));
                    } else {
                        targetEl.classList.remove('show');
                        targetEl.querySelectorAll('input, textarea').forEach(i => i.removeAttribute('required'));
                    }
                });
            } else {
                const radios = document.querySelectorAll(cond.trigger);
                radios.forEach(radio => {
                    radio.addEventListener('change', () => {
                        const isYes = document.querySelector(cond.trigger + '[value="1"]').checked;
                        if (isYes) {
                            targetEl.classList.add('show');
                            targetEl.querySelectorAll('input, textarea').forEach(i => i.setAttribute('required', 'required'));
                        } else {
                            targetEl.classList.remove('show');
                            targetEl.querySelectorAll('input, textarea').forEach(i => i.removeAttribute('required'));
                        }
                    });
                });
            }
        });
    }
    
    // ── Items Logic (Dynamic Addition & Deletion) ───────────────────────────
    let itemCounter = 0;
    
    function createItemCard(type = 'material') {
        const index = itemCounter++;
        const isMaterial = type === 'material';
        
        const card = document.createElement('div');
        card.className = 'item-card';
        card.dataset.index = index;
        card.dataset.type = type;
        
        card.innerHTML = `
            <div class="item-header">
                <span class="item-number">Item #${index + 1} (${isMaterial ? 'Material' : 'Serviço'})</span>
                <button type="button" class="btn btn-sm btn-danger btn-remove-item"><i class="ph ph-trash"></i></button>
            </div>
            
            <input type="hidden" name="items[${index}][item_type]" value="${type}">
            <input type="hidden" name="items[${index}][source_system]" value="compras_gov">
            <input type="hidden" name="items[${index}][catmat_group]" class="item-catmat-group">
            <input type="hidden" name="items[${index}][catmat_class]" class="item-catmat-class">
            <input type="hidden" name="items[${index}][catmat_pdm]" class="item-catmat-pdm">
            <input type="hidden" name="items[${index}][is_sustainable]" class="item-sustainable">
            <input type="hidden" name="items[${index}][price_median]" class="item-price-median">
            <input type="hidden" name="items[${index}][price_min]" class="item-price-min">
            <input type="hidden" name="items[${index}][price_max]" class="item-price-max">
            <input type="hidden" name="items[${index}][price_sample_count]" class="item-price-sample">

            <div class="form-row" style="grid-template-columns: 2fr 1fr;">
                <div class="form-group search-container">
                    <label>Busca CATMAT/CATSER (Automática) *</label>
                    <input type="text" class="item-search-input" placeholder="Digite para buscar..." required autocomplete="off">
                    <div class="search-results"></div>
                </div>
                <div class="form-group">
                    <label>Código do Catálogo</label>
                    <input type="text" name="items[${index}][catalog_code]" class="item-catalog-code" readonly>
                </div>
            </div>

            <div class="form-row-3">
                <div class="form-group" style="grid-column: span 3;">
                    <label>Descrição Detalhada *</label>
                    <textarea name="items[${index}][description]" class="item-description" required rows="2" placeholder="Ex: Caneta esferográfica azul, ponta 1.0mm..."></textarea>
                </div>
                <div class="form-group">
                    <label>Unidade *</label>
                    <input type="text" name="items[${index}][unit]" class="item-unit" required placeholder="Ex: CX">
                </div>
                <div class="form-group">
                    <label>Quantidade *</label>
                    <input type="number" name="items[${index}][quantity]" class="item-quantity" required step="0.0001" min="0.0001">
                </div>
                <div class="form-group">
                    <label>Valor Unitário Estimado (R$)</label>
                    <input type="number" name="items[${index}][unit_value]" class="item-unit-value" step="0.01" min="0" placeholder="Busca automática...">
                </div>
            </div>
            
            <div class="form-group">
                <label>Memória de Cálculo da Quantidade</label>
                <input type="text" name="items[${index}][memory_calculation]" placeholder="Ex: 5 caixas por escola x 10 escolas = 50">
            </div>

            <div class="item-totals">
                <div>Valor Total Estimado: <span class="total-value item-total-display">R$ 0,00</span></div>
                <div class="item-price-meta conditional"></div>
            </div>
        `;
        
        itemsContainer.appendChild(card);
        setupItemCardListeners(card, index, type);
    }
    
    function setupItemCardListeners(card, index, type) {
        // Remove item
        card.querySelector('.btn-remove-item').addEventListener('click', () => {
            card.remove();
            calculateTotalsAndFraming();
        });
        
        // Calculate total when quantity or unit value changes
        const qtyInput = card.querySelector('.item-quantity');
        const valInput = card.querySelector('.item-unit-value');
        const totalDisplay = card.querySelector('.item-total-display');
        
        const calcTotal = () => {
            const qty = parseFloat(qtyInput.value) || 0;
            const val = parseFloat(valInput.value) || 0;
            const total = qty * val;
            totalDisplay.textContent = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(total);
        };
        
        qtyInput.addEventListener('input', calcTotal);
        valInput.addEventListener('input', calcTotal);
        
        // CATMAT/CATSER Search Logic
        const searchInput = card.querySelector('.item-search-input');
        const resultsContainer = card.querySelector('.search-results');
        let searchTimeout;
        
        searchInput.addEventListener('input', (e) => {
            const term = e.target.value.trim();
            clearTimeout(searchTimeout);
            
            if (term.length < 3) {
                resultsContainer.classList.remove('show');
                return;
            }
            
            resultsContainer.innerHTML = '<div class="search-loading"><div class="spinner"></div> Buscando catálogo...</div>';
            resultsContainer.classList.add('show');
            
            searchTimeout = setTimeout(() => performSearch(term, type, resultsContainer, card), 600);
        });
        
        // Hide search results when clicking outside
        document.addEventListener('click', (e) => {
            if (!card.querySelector('.search-container').contains(e.target)) {
                resultsContainer.classList.remove('show');
            }
        });
    }
    
    async function performSearch(term, type, resultsContainer, card) {
        try {
            const endpoint = type === 'material' ? '/api/compras-gov/material/items' : '/api/compras-gov/service/items';
            const queryParam = type === 'material' ? 'descricaoItem' : 'descricaoServico';
            
            const response = await fetch(`${endpoint}?${queryParam}=${encodeURIComponent(term)}&tamanhoPagina=10`);
            const data = await response.json();
            
            if (data.error || !data.resultado || data.resultado.length === 0) {
                resultsContainer.innerHTML = '<div class="search-loading">Nenhum item encontrado.</div>';
                return;
            }
            
            renderSearchResults(data.resultado, type, resultsContainer, card);
        } catch (error) {
            resultsContainer.innerHTML = '<div class="search-loading">Erro na busca. Verifique a conexão.</div>';
        }
    }
    
    function renderSearchResults(results, type, resultsContainer, card) {
        resultsContainer.innerHTML = '';
        
        results.forEach(item => {
            const isMaterial = type === 'material';
            const code = isMaterial ? item.codigoItem : item.codigoServico;
            // A API v3 retorna 'descricaoItem' para material, mas 'nomeServico' para serviços!
            const desc = isMaterial ? item.descricaoItem : (item.nomeServico || item.descricaoServico);
            
            const div = document.createElement('div');
            div.className = 'search-result-item';
            div.innerHTML = `
                <div class="result-code">${code} ${item.itemSustentavel ? '<span style="color:#10b981">🌱 Sustentável</span>' : ''}</div>
                <div class="result-desc">${desc}</div>
                <div class="result-meta">
                    ${isMaterial && item.nomeClasse ? `Classe: ${item.nomeClasse}` : (item.nomeClasse ? `Classe: ${item.nomeClasse}` : '')}
                </div>
            `;
            
            div.addEventListener('click', () => selectItemFromCatalog(item, type, card, resultsContainer));
            resultsContainer.appendChild(div);
        });
    }
    
    async function selectItemFromCatalog(item, type, card, resultsContainer) {
        const isMaterial = type === 'material';
        const code = isMaterial ? item.codigoItem : item.codigoServico;
        const desc = isMaterial ? item.descricaoItem : (item.nomeServico || item.descricaoServico);
        
        // Populate fields
        card.querySelector('.item-search-input').value = desc;
        card.querySelector('.item-catalog-code').value = code;
        card.querySelector('.item-description').value = desc;
        card.querySelector('.item-sustainable').value = item.itemSustentavel ? 1 : 0;
        
        // Default values
        card.querySelector('.item-quantity').value = 1;

        if (isMaterial) {
            card.querySelector('.item-catmat-group').value = item.codigoGrupo || '';
            card.querySelector('.item-catmat-class').value = item.codigoClasse || '';
            card.querySelector('.item-catmat-pdm').value = item.codigoPadraoDescricaoMaterial || '';
            
            if (item.unidadeMedidaPadrao) {
                card.querySelector('.item-unit').value = item.unidadeMedidaPadrao;
            } else {
                card.querySelector('.item-unit').value = "UN";
            }
        } else {
            card.querySelector('.item-unit').value = "SV"; // Serviço padrão
        }
        
        resultsContainer.classList.remove('show');
        
        // Automatically trigger price research
        fetchPricesForItem(code, type, card);
    }
    
    async function fetchPricesForItem(code, type, card) {
        const metaContainer = card.querySelector('.item-price-meta');
        const unitValInput = card.querySelector('.item-unit-value');
        
        metaContainer.innerHTML = '<span class="spinner" style="width:12px;height:12px"></span> Pesquisando melhor referência de preços...';
        metaContainer.classList.add('show');
        
        try {
            const endpoint = type === 'material' ? '/api/compras-gov/material/prices' : '/api/compras-gov/service/prices';
            const queryParam = type === 'material' ? 'codigoItemCatalogo' : 'codigoServico';
            const desc = card.querySelector('.item-description').value || '';
            
            const endDate = new Date().toISOString().split('T')[0];
            const startDate = new Date(new Date().setFullYear(new Date().getFullYear() - 1)).toISOString().split('T')[0];
            
            const response = await fetch(`${endpoint}?${queryParam}=${code}&descricao=${encodeURIComponent(desc)}&dataInicial=${startDate}&dataFinal=${endDate}&tamanhoPagina=50`);
            const data = await response.json();
            
            if (!data.resultado || data.resultado.length === 0) {
                metaContainer.innerHTML = 'Sem referências de preço recentes.';
                return;
            }
            
            // Calculate median
            const prices = data.resultado.map(p => type === 'material' ? p.valorUnitario : p.valorUnitarioHomologado).filter(p => p > 0).sort((a, b) => a - b);
            
            if (prices.length === 0) {
                 metaContainer.innerHTML = 'Preços inválidos encontrados.';
                 return;
            }
            
            const median = prices.length % 2 !== 0 ? prices[Math.floor(prices.length / 2)] : (prices[prices.length / 2 - 1] + prices[prices.length / 2]) / 2;
            const min = prices[0];
            const max = prices[prices.length - 1];
            
            // Source definition from hybrid API
            const source = data.fonte || "PNCP";
            const levelClass = data.nivel === 1 ? 'color: var(--success)' : (data.nivel === 2 ? 'color: #3b82f6' : 'color: var(--warning)');
            
            // Populate hidden fields
            card.querySelector('.item-price-median').value = median.toFixed(2);
            card.querySelector('.item-price-min').value = min.toFixed(2);
            card.querySelector('.item-price-max').value = max.toFixed(2);
            card.querySelector('.item-price-sample').value = prices.length;
            
            // Set value input
            if (!unitValInput.value) {
                unitValInput.value = median.toFixed(2);
                unitValInput.dispatchEvent(new Event('input'));
            }
            
            metaContainer.innerHTML = `
                <div style="font-size: 0.75rem; margin-bottom: 4px; ${levelClass}"><strong><i class="ph ph-database"></i> ${source}</strong></div>
                Média/Mediana Estimada: <strong>R$ ${median.toFixed(2).replace('.', ',')}</strong> | 
                Baseado em ${prices.length} compras.
            `;
        } catch (error) {
            metaContainer.innerHTML = 'Falha ao pesquisar preços.';
        }
    }
    
    // ── Calculations & Framing ──────────────────────────────────────────────
    function calculateTotalsAndFraming() {
        const itemCards = itemsContainer.querySelectorAll('.item-card');
        let totalMaterials = 0;
        let totalServices = 0;
        let totalGeneral = 0;
        
        itemCards.forEach(card => {
            const type = card.dataset.type;
            const qty = parseFloat(card.querySelector('.item-quantity').value) || 0;
            const val = parseFloat(card.querySelector('.item-unit-value').value) || 0;
            const total = qty * val;
            
            totalGeneral += total;
            if (type === 'material') totalMaterials += total;
            else totalServices += total;
        });
        
        // Update UI displays in step 4
        document.getElementById('display-total-geral').textContent = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(totalGeneral);
        document.getElementById('hidden-total-estimated').value = totalGeneral.toFixed(2);
        
        const framingBanner = document.getElementById('legal-framing-banner');
        
        if (totalServices > 0 && totalGeneral <= thresholds.inciso_ii) {
            framingBanner.className = 'legal-banner dispensa';
            framingBanner.innerHTML = `<i class="ph-fill ph-check-circle icon"></i> 
                <div><strong>Dispensa de Licitação (Serviços)</strong><br>
                <span style="font-size:0.8rem">Valor abaixo do limite do Art. 75, Inciso II (R$ ${thresholds.inciso_ii.toLocaleString('pt-BR', {minimumFractionDigits: 2})})</span></div>`;
        } else if (totalMaterials > 0 && totalServices === 0 && totalGeneral <= thresholds.inciso_i) {
            framingBanner.className = 'legal-banner dispensa';
            framingBanner.innerHTML = `<i class="ph-fill ph-check-circle icon"></i> 
                <div><strong>Dispensa de Licitação (Materiais)</strong><br>
                <span style="font-size:0.8rem">Valor abaixo do limite do Art. 75, Inciso I (R$ ${thresholds.inciso_i.toLocaleString('pt-BR', {minimumFractionDigits: 2})})</span></div>`;
        } else {
            framingBanner.className = 'legal-banner licitacao';
            framingBanner.innerHTML = `<i class="ph-fill ph-warning-circle icon"></i> 
                <div><strong>Licitação Obrigatória</strong><br>
                <span style="font-size:0.8rem">Valor estimado ultrapassa os limites de dispensa do Art. 75. Recomenda-se Pregão Eletrônico ou Concorrência.</span></div>`;
        }
    }
    
    // ── Review Step ─────────────────────────────────────────────────────────
    function populateReviewStep() {
        // Just a simple visual confirmation for now
        const summary = document.getElementById('review-summary');
        const title = document.getElementById('title').value;
        const secretaria = document.querySelector('select[name="secretaria"] option:checked').text;
        const total = document.getElementById('display-total-geral').textContent;
        const itemsCount = itemsContainer.querySelectorAll('.item-card').length;
        
        summary.innerHTML = `
            <div class="alert alert-info" style="margin-bottom: 24px;">
                <i class="ph ph-info"></i>
                <div>
                    Você está prestes a gerar a <strong>Solicitação de Demanda</strong>, o <strong>Estudo Técnico Preliminar</strong> e o <strong>Termo de Referência</strong>.
                </div>
            </div>
            
            <table class="summary-table">
                <tr><th>Unidade Requisitante</th><td>${secretaria}</td></tr>
                <tr><th>Objeto</th><td>${title}</td></tr>
                <tr><th>Qtd. Itens</th><td>${itemsCount}</td></tr>
                <tr class="total-row"><th>Valor Total Estimado</th><td>${total}</td></tr>
            </table>
        `;
    }

    // ── Team Signatures ─────────────────────────────────────────────────────
    const btnAddTeamMember = document.getElementById('btn-add-team-member');
    const teamMembersContainer = document.getElementById('team-members-container');
    let teamCounter = 0;

    btnAddTeamMember.addEventListener('click', () => {
        const index = teamCounter++;
        const row = document.createElement('div');
        row.className = 'form-row-3';
        row.style.marginBottom = '12px';
        row.innerHTML = `
            <div class="form-group" style="margin-bottom: 0;">
                <input type="text" name="study[team_signatures][${index}][name]" placeholder="Nome do membro" required>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <input type="text" name="study[team_signatures][${index}][role]" placeholder="Cargo/Função">
            </div>
            <button type="button" class="btn btn-danger btn-sm" onclick="this.parentElement.remove()" style="align-self: flex-start; height: 42px;">Remover</button>
        `;
        teamMembersContainer.appendChild(row);
    });

    // ── Init ────────────────────────────────────────────────────────────────
    btnAddItem.addEventListener('click', () => createItemCard('material'));
    document.getElementById('btn-add-service').addEventListener('click', () => createItemCard('service'));
    
    // Start with one item
    createItemCard('material');
    setupConditionals();
    updateWizard();
});
