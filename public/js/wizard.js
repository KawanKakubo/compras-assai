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

            <div class="hierarchy-navigation">
                <div class="hierarchy-step" data-level="0">
                    <label><i class="ph ph-list-numbers"></i> Selecione o ${isMaterial ? 'Grupo' : 'Seção'}</label>
                    <select class="hierarchy-select" data-level="0">
                        <option value="">Carregando opções...</option>
                    </select>
                </div>
                <div class="hierarchy-steps-container"></div>
                <div class="item-selection-container" style="display:none">
                    <label><i class="ph ph-package"></i> Selecione o Item Final</label>
                    <div class="item-selection-list"></div>
                </div>
            </div>

            <div class="form-row" style="grid-template-columns: 1fr;">
                <div class="form-group">
                    <label>Código do Catálogo</label>
                    <input type="text" name="items[${index}][catalog_code]" class="item-catalog-code" readonly placeholder="Selecionado via catálogo">
                </div>
            </div>

            <div class="form-row-3">
                <div class="form-group" style="grid-column: span 3;">
                    <label>Descrição Detalhada *</label>
                    <textarea name="items[${index}][description]" class="item-description" required rows="2" placeholder="A descrição será preenchida automaticamente ao selecionar o item..."></textarea>
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
        setupHierarchyNavigation(card, type);
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
    }

    async function setupHierarchyNavigation(card, type) {
        const initialSelect = card.querySelector('.hierarchy-select[data-level="0"]');
        const stepsContainer = card.querySelector('.hierarchy-steps-container');
        const itemContainer = card.querySelector('.item-selection-container');
        const itemList = card.querySelector('.item-selection-list');

        // Load initial level (Group or Section)
        const isMaterial = type === 'material';
        const endpoint = isMaterial ? '/api/compras-gov/material/groups' : '/api/compras-gov/service/sections';
        
        try {
            const response = await fetch(endpoint);
            const data = await response.json();
            const results = data.resultado || [];

            initialSelect.innerHTML = '<option value="">Selecione...</option>';
            results.forEach(item => {
                const opt = document.createElement('option');
                opt.value = isMaterial ? item.codigoGrupo : item.codigoSecao;
                opt.textContent = `${opt.value} - ${isMaterial ? item.nomeGrupo : item.nomeSecao}`;
                initialSelect.appendChild(opt);
            });

            initialSelect.addEventListener('change', () => {
                stepsContainer.innerHTML = '';
                itemContainer.style.display = 'none';
                if (initialSelect.value) {
                    loadNextLevel(card, type, 1, initialSelect.value, stepsContainer, itemContainer, itemList);
                }
            });
        } catch (error) {
            initialSelect.innerHTML = '<option value="">Erro ao carregar</option>';
        }
    }

    async function loadNextLevel(card, type, level, parentCode, container, itemContainer, itemList) {
        const isMaterial = type === 'material';
        let endpoint = '';
        let label = '';
        let nextLevel = level + 1;

        if (isMaterial) {
            if (level === 1) { endpoint = `/api/compras-gov/material/classes?codigoGrupo=${parentCode}`; label = 'Classe'; }
            else if (level === 2) { endpoint = `/api/compras-gov/material/pdms?codigoClasse=${parentCode}`; label = 'PDM (Padrão de Descritivo)'; }
            else {
                // Load final items for PDM
                loadFinalItems(card, type, parentCode, itemContainer, itemList);
                return;
            }
        } else {
            if (level === 1) { endpoint = `/api/compras-gov/service/divisions?codigoSecao=${parentCode}`; label = 'Divisão'; }
            else if (level === 2) { endpoint = `/api/compras-gov/service/groups?codigoDivisao=${parentCode}`; label = 'Grupo'; }
            else if (level === 3) { endpoint = `/api/compras-gov/service/classes?codigoGrupo=${parentCode}`; label = 'Classe'; }
            else if (level === 4) { endpoint = `/api/compras-gov/service/subclasses?codigoClasse=${parentCode}`; label = 'Subclasse'; }
            else {
                // Load final items for Subclass
                loadFinalItems(card, type, parentCode, itemContainer, itemList);
                return;
            }
        }

        const stepDiv = document.createElement('div');
        stepDiv.className = 'hierarchy-step';
        stepDiv.dataset.level = level;
        stepDiv.innerHTML = `
            <label><i class="ph ph-caret-right"></i> Selecione a ${label}</label>
            <select class="hierarchy-select">
                <option value="">Carregando...</option>
            </select>
        `;
        container.appendChild(stepDiv);

        const select = stepDiv.querySelector('select');

        try {
            const response = await fetch(endpoint, {
                headers: { 'Accept': 'application/json' }
            });
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                select.innerHTML = `<option value="">Erro: ${errorData.message || 'Falha no servidor'}</option>`;
                return;
            }

            const data = await response.json();
            
            if (data.error) {
                select.innerHTML = `<option value="">Erro: ${data.error}</option>`;
                return;
            }

            const results = data.resultado || [];

            select.innerHTML = '<option value="">Selecione...</option>';
            results.forEach(item => {
                const opt = document.createElement('option');
                if (isMaterial) {
                    opt.value = level === 1 ? item.codigoClasse : item.codigoPdm;
                    opt.textContent = `${opt.value} - ${level === 1 ? item.nomeClasse : item.nomePdm}`;
                } else {
                    const fields = ['codigoDivisao', 'codigoGrupo', 'codigoClasse', 'codigoSubclasse'];
                    const names = ['nomeDivisao', 'nomeGrupo', 'nomeClasse', 'nomeSubclasse'];
                    opt.value = item[fields[level-1]];
                    opt.textContent = `${opt.value} - ${item[names[level-1]]}`;
                }
                select.appendChild(opt);
            });

            select.addEventListener('change', () => {
                // Remove subsequent steps
                while (stepDiv.nextElementSibling) {
                    stepDiv.nextElementSibling.remove();
                }
                itemContainer.style.display = 'none';
                
                if (select.value) {
                    loadNextLevel(card, type, nextLevel, select.value, container, itemContainer, itemList);
                }
            });
        } catch (error) {
            select.innerHTML = '<option value="">Erro na conexão com o servidor</option>';
            console.error('Taxonomy Error:', error);
        }
    }

    async function loadFinalItems(card, type, parentCode, itemContainer, itemList) {
        const isMaterial = type === 'material';
        const endpoint = isMaterial 
            ? `/api/compras-gov/material/items?codigoPdm=${parentCode}&statusItem=true&tamanhoPagina=50`
            : `/api/compras-gov/service/items?codigoSubclasse=${parentCode}&statusServico=true&tamanhoPagina=50`;

        itemContainer.style.display = 'block';
        itemList.innerHTML = '<div class="search-loading"><div class="spinner"></div> Carregando itens finais...</div>';

        try {
            const response = await fetch(endpoint, {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                itemList.innerHTML = `<div class="search-loading" style="color:var(--danger)">Erro: ${errorData.message || 'Falha no servidor'}</div>`;
                return;
            }

            const data = await response.json();

            if (data.error) {
                itemList.innerHTML = `<div class="search-loading" style="color:var(--danger)">Erro: ${data.error}</div>`;
                return;
            }

            const results = data.resultado || [];

            if (results.length === 0) {
                itemList.innerHTML = '<div class="search-loading">Nenhum item ativo encontrado nesta categoria.</div>';
                return;
            }

            itemList.innerHTML = '';
            results.forEach(item => {
                const div = document.createElement('div');
                div.className = 'item-selection-option';
                const code = isMaterial ? item.codigoItem : item.codigoServico;
                const name = isMaterial ? item.descricaoItem : (item.nomeServico || item.descricaoServico);
                
                div.innerHTML = `
                    <span class="item-code">${code}</span>
                    <span class="item-name">${name}</span>
                `;
                
                div.addEventListener('click', () => {
                    selectItemFromCatalog(item, type, card);
                    // Visual feedback
                    itemList.querySelectorAll('.item-selection-option').forEach(el => el.style.background = '');
                    div.style.background = 'var(--accent-glow)';
                });
                itemList.appendChild(div);
            });
        } catch (error) {
            itemList.innerHTML = '<div class="search-loading" style="color:var(--danger)">Erro na conexão com o servidor.</div>';
        }
    }
    
    async function selectItemFromCatalog(item, type, card) {
        const isMaterial = type === 'material';
        const code = isMaterial ? item.codigoItem : item.codigoServico;
        const desc = isMaterial ? item.descricaoItem : (item.nomeServico || item.descricaoServico);
        
        // Populate fields
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
            
            // Set exact unit of measurement returned from PNCP to align with price
            if (type === 'material' && data.resultado[0].unidadeMedida) {
                const apiUnit = data.resultado[0].unidadeMedida.toUpperCase();
                const unitInput = card.querySelector('.item-unit');
                if (unitInput && apiUnit && apiUnit !== 'UN') {
                    unitInput.value = apiUnit;
                }
            }
            
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
        const secretariaEl = document.querySelector('select[name="secretaria"]') || document.querySelector('input[name="secretaria"]');
        let secretaria = '';
        if (secretariaEl) {
            secretaria = secretariaEl.tagName === 'SELECT' ? (secretariaEl.options[secretariaEl.selectedIndex]?.text || '') : (secretariaEl.dataset.name || secretariaEl.value);
        }
        const total = document.getElementById('display-total-geral').textContent;
        const itemsCount = itemsContainer.querySelectorAll('.item-card').length;
        
        summary.innerHTML = `
            <div class="alert alert-info" style="margin-bottom: 24px;">
                <i class="ph ph-info"></i>
                <div>
                    Você está prestes a gerar a <strong>Solicitação de Demanda</strong> e o <strong>Estudo Técnico Preliminar</strong>.
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

    // ── Masks ──────────────────────────────────────────────────────────────
    function applyCpfMask(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 11) value = value.slice(0, 11);
        
        let masked = value;
        if (value.length > 3) masked = value.slice(0, 3) + '.' + value.slice(3);
        if (value.length > 6) masked = masked.slice(0, 7) + '.' + masked.slice(7);
        if (value.length > 9) masked = masked.slice(0, 11) + '-' + masked.slice(11);
        
        e.target.value = masked;
    }

    document.addEventListener('input', (e) => {
        if (e.target.classList.contains('mask-cpf')) {
            applyCpfMask(e);
        }
    });

    // ── Init ────────────────────────────────────────────────────────────────
    btnAddItem.addEventListener('click', () => createItemCard('material'));
    document.getElementById('btn-add-service').addEventListener('click', () => createItemCard('service'));
    
    // Start with one item
    createItemCard('material');
    setupConditionals();
    updateWizard();
});
