// ============================================
// VARIÁVEIS GLOBAIS
// ============================================
let callHistory = [];

// ============================================
// INICIALIZAÇÃO
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    // Carregar histórico do localStorage
    loadHistory();

    // Configurar event listeners
    setupEventListeners();

    // Configurar cálculos automáticos
    setupAutoCalculations();

    console.log('✅ Sistema de Cobrança inicializado');
}

// ============================================
// EVENT LISTENERS
// ============================================
function setupEventListeners() {
    const form = document.getElementById('callForm');
    form.addEventListener('submit', handleFormSubmit);

    // Formatação automática de telefone
    const phoneInput = document.getElementById('to');
    phoneInput.addEventListener('input', formatPhoneNumber);

    // Formatação automática de valores monetários
    const valorDevidoInput = document.getElementById('valor_devido');
    valorDevidoInput.addEventListener('input', formatCurrency);

    // Formatação automática de CPF
    const cpfInput = document.getElementById('cpf');
    cpfInput.addEventListener('input', formatCPF);
}

// ============================================
// CÁLCULOS AUTOMÁTICOS
// ============================================
function setupAutoCalculations() {
    const valorDevidoInput = document.getElementById('valor_devido');
    const percentualDescontoInput = document.getElementById('percentual_desconto');
    const maxParcelasInput = document.getElementById('max_parcelas');

    // Calcular quando qualquer um desses campos mudar
    valorDevidoInput.addEventListener('input', calculateValues);
    percentualDescontoInput.addEventListener('input', calculateValues);
    maxParcelasInput.addEventListener('input', calculateValues);
}

function calculateValues() {
    const valorDevidoStr = document.getElementById('valor_devido').value;
    const percentualDesconto = parseFloat(document.getElementById('percentual_desconto').value) || 10;
    const maxParcelas = parseInt(document.getElementById('max_parcelas').value) || 3;

    // Converter valor devido para número
    const valorDevido = parseCurrency(valorDevidoStr);

    if (valorDevido > 0) {
        // Calcular valor com desconto
        const valorComDesconto = valorDevido * (1 - (percentualDesconto / 100));
        document.getElementById('valor_com_desconto').value = formatCurrencyValue(valorComDesconto);

        // Calcular valor da parcela
        const valorParcela = valorDevido / maxParcelas;
        document.getElementById('valor_parcela').value = formatCurrencyValue(valorParcela);
    }
}

// ============================================
// FORMATAÇÃO
// ============================================
function formatPhoneNumber(e) {
    let value = e.target.value.replace(/\D/g, '');

    // Se começar com 55, é formato internacional
    if (value.startsWith('55')) {
        // Formato: +55 11 99999-9999
        if (value.length <= 2) {
            e.target.value = '+' + value;
        } else if (value.length <= 4) {
            e.target.value = '+' + value.slice(0, 2) + value.slice(2);
        } else if (value.length <= 13) {
            e.target.value = '+' + value.slice(0, 2) + value.slice(2, 4) + value.slice(4);
        }
    } else {
        // Formato nacional: (11) 99999-9999
        if (value.length <= 2) {
            e.target.value = value;
        } else if (value.length <= 6) {
            e.target.value = '(' + value.slice(0, 2) + ') ' + value.slice(2);
        } else if (value.length <= 10) {
            e.target.value = '(' + value.slice(0, 2) + ') ' + value.slice(2, 6) + '-' + value.slice(6);
        } else {
            e.target.value = '(' + value.slice(0, 2) + ') ' + value.slice(2, 7) + '-' + value.slice(7, 11);
        }
    }
}

function formatCurrency(e) {
    let value = e.target.value.replace(/\D/g, '');

    if (value === '') {
        e.target.value = '';
        return;
    }

    value = (parseInt(value) / 100).toFixed(2);
    e.target.value = value.replace('.', ',');
}

function formatCurrencyValue(value) {
    return value.toFixed(2).replace('.', ',');
}

function parseCurrency(valueStr) {
    if (!valueStr) return 0;
    return parseFloat(valueStr.replace(/\./g, '').replace(',', '.')) || 0;
}


// ============================================
// FORMATAÇÃO E VALIDAÇÃO DE CPF
// ============================================
function formatCPF(e) {
    let value = e.target.value.replace(/\D/g, '');
    
    if (value.length <= 11) {
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    }
    
    e.target.value = value;
}

function validateCPF(cpf) {
    // Remove formatação
    cpf = cpf.replace(/\D/g, '');
    
    // Verifica se tem 11 dígitos
    if (cpf.length !== 11) {
        return false;
    }
    
    // Verifica se todos os dígitos são iguais
    if (/^(\d)\1+$/.test(cpf)) {
        return false;
    }
    
    // Validação do primeiro dígito verificador
    let soma = 0;
    for (let i = 0; i < 9; i++) {
        soma += parseInt(cpf.charAt(i)) * (10 - i);
    }
    let resto = soma % 11;
    let digito1 = resto < 2 ? 0 : 11 - resto;
    
    if (parseInt(cpf.charAt(9)) !== digito1) {
        return false;
    }
    
    // Validação do segundo dígito verificador
    soma = 0;
    for (let i = 0; i < 10; i++) {
        soma += parseInt(cpf.charAt(i)) * (11 - i);
    }
    resto = soma % 11;
    let digito2 = resto < 2 ? 0 : 11 - resto;
    
    if (parseInt(cpf.charAt(10)) !== digito2) {
        return false;
    }
    
    return true;
}

function cleanCPF(cpf) {
    return cpf.replace(/\D/g, '');
}
function formatPhoneToInternational(phone) {
    // Remove tudo que não é número
    let cleaned = phone.replace(/\D/g, '');

    // Se não começar com 55, adiciona
    if (!cleaned.startsWith('55')) {
        cleaned = '55' + cleaned;
    }

    // Adiciona o +
    return '+' + cleaned;
}

// ============================================
// MANIPULAÇÃO DE FORMULÁRIO
// ============================================
function handleFormSubmit(e) {
    e.preventDefault();

    const formData = getFormData();

    if (validateFormData(formData)) {
        sendCallRequest(formData);
    }
}

function getFormData() {
    const form = document.getElementById('callForm');
    const formData = new FormData(form);
    const data = {};

    for (let [key, value] of formData.entries()) {
        if (value.trim() !== '') {
            data[key] = value;
        }
    }

    // Formatar telefone para formato internacional
    data.to = formatPhoneToInternational(data.to);

    // Converter campos numéricos
    if (data.valor_devido) {
        data.valor_devido = parseCurrency(data.valor_devido);
    }
    if (data.max_parcelas) {
        data.max_parcelas = parseInt(data.max_parcelas);
    }
    if (data.max_parcelas_estendidas) {
        data.max_parcelas_estendidas = parseInt(data.max_parcelas_estendidas);
    }

    return data;
}

function validateFormData(data) {
    const required = ['to', 'primeiro_nome', 'cpf', 'data_nascimento', 'empresa_credora', 'valor_devido', 'data_vencimento'];

    for (let field of required) {
        if (!data[field]) {
            showAlert('error', 'Erro de Validação', `O campo "${getFieldLabel(field)}" é obrigatório.`);
            return false;
        }
    }

    // Validar CPF
    if (data.cpf && !validateCPF(data.cpf)) {
        showAlert('error', 'CPF Inválido', 'Por favor, informe um CPF válido.');
        return false;
    }

    // Limpar CPF (remover formatação para envio)
    if (data.cpf) {
        data.cpf = cleanCPF(data.cpf);
    }

    return true;
}

function getFieldLabel(fieldName) {
    const labels = {
        'to': 'Telefone',
        'primeiro_nome': 'Primeiro Nome',
        'empresa_credora': 'Empresa Credora',
        'valor_devido': 'Valor Devido',
        'data_vencimento': 'Data de Vencimento',
        'cpf': 'CPF',
        'data_nascimento': 'Data de Nascimento'
    };
    return labels[fieldName] || fieldName;
}

function resetForm() {
    document.getElementById('callForm').reset();
    document.getElementById('valor_com_desconto').value = '';
    document.getElementById('valor_parcela').value = '';
    hideStatus();
}

// ============================================
// REQUISIÇÃO API
// ============================================
async function sendCallRequest(data) {
    showLoading();

    try {
        console.log('📤 Enviando requisição:', data);
        console.log('📍 Endpoint:', API_CONFIG.endpoint);

        const response = await fetch(API_CONFIG.endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                ...API_CONFIG.headers
            },
            body: JSON.stringify(data)
        });

        console.log('📥 Status da resposta:', response.status, response.statusText);

        // Verificar se a resposta é JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const textResponse = await response.text();
            console.error('❌ Resposta não é JSON:', textResponse.substring(0, 500));
            hideLoading();
            showAlert('error', 'Erro no Servidor',
                `O servidor retornou uma resposta inválida (HTML ao invés de JSON).
                Verifique se o servidor backend está rodando corretamente.
                Status: ${response.status} ${response.statusText}`);
            return;
        }

        const result = await response.json();
        console.log('📦 Resposta do servidor:', result);

        hideLoading();

        if (response.ok && result.success) {
            handleSuccess(result, data);
        } else {
            handleError(result);
        }

    } catch (error) {
        hideLoading();
        console.error('❌ Erro na requisição:', error);
        showAlert('error', 'Erro de Conexão',
            `Não foi possível conectar ao servidor: ${error.message}\n\n` +
            `Verifique:\n` +
            `1. Se o servidor backend está rodando\n` +
            `2. Se o ngrok está ativo\n` +
            `3. Se a URL no config.js está correta`);
    }
}

function handleSuccess(result, originalData) {
    console.log('✅ Chamada iniciada com sucesso:', result);

    // Mostrar status de sucesso
    showStatus('success', {
        title: 'Chamada Iniciada com Sucesso!',
        callId: result.call_id,
        status: result.call_status,
        agentId: result.agent_id,
        message: result.message
    });

    // Adicionar ao histórico
    addToHistory({
        ...originalData,
        ...result,
        timestamp: new Date().toISOString(),
        success: true
    });

    // Limpar formulário
    setTimeout(() => {
        resetForm();
    }, 2000);
}

function handleError(error) {
    console.error('❌ Erro na chamada:', error);

    const errorMessage = error.error?.message || error.message || 'Erro desconhecido ao iniciar chamada.';

    showStatus('error', {
        title: 'Erro ao Iniciar Chamada',
        message: errorMessage,
        details: error.error
    });

    // Adicionar ao histórico
    addToHistory({
        timestamp: new Date().toISOString(),
        success: false,
        error: errorMessage
    });
}

// ============================================
// UI - STATUS
// ============================================
function showStatus(type, data) {
    const statusCard = document.getElementById('statusCard');
    const statusContent = document.getElementById('statusContent');

    let html = '';

    if (type === 'success') {
        html = `
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <div class="alert-content">
                    <h4>${data.title}</h4>
                    <p><strong>Call ID:</strong> ${data.callId}</p>
                    <p><strong>Status:</strong> ${data.status}</p>
                    <p><strong>Agent ID:</strong> ${data.agentId}</p>
                    <p>${data.message}</p>
                </div>
            </div>
        `;
    } else if (type === 'error') {
        html = `
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <div class="alert-content">
                    <h4>${data.title}</h4>
                    <p>${data.message}</p>
                    ${data.details ? `<p><small>${JSON.stringify(data.details)}</small></p>` : ''}
                </div>
            </div>
        `;
    }

    statusContent.innerHTML = html;
    statusCard.style.display = 'block';

    // Scroll para o status
    statusCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function hideStatus() {
    document.getElementById('statusCard').style.display = 'none';
}

function showAlert(type, title, message) {
    showStatus(type, { title, message });
}

// ============================================
// UI - LOADING
// ============================================
function showLoading() {
    const modal = document.getElementById('loadingModal');
    modal.classList.add('active');
    document.getElementById('submitBtn').disabled = true;
}

function hideLoading() {
    const modal = document.getElementById('loadingModal');
    modal.classList.remove('active');
    document.getElementById('submitBtn').disabled = false;
}

// ============================================
// HISTÓRICO
// ============================================
function addToHistory(item) {
    callHistory.unshift(item);

    // Limitar histórico a 50 itens
    if (callHistory.length > 50) {
        callHistory = callHistory.slice(0, 50);
    }

    saveHistory();
    renderHistory();
}

function saveHistory() {
    try {
        localStorage.setItem('callHistory', JSON.stringify(callHistory));
    } catch (error) {
        console.error('Erro ao salvar histórico:', error);
    }
}

function loadHistory() {
    try {
        const saved = localStorage.getItem('callHistory');
        if (saved) {
            callHistory = JSON.parse(saved);
            renderHistory();
        }
    } catch (error) {
        console.error('Erro ao carregar histórico:', error);
        callHistory = [];
    }
}

function clearHistory() {
    if (confirm('Tem certeza que deseja limpar todo o histórico?')) {
        callHistory = [];
        saveHistory();
        renderHistory();
        showAlert('info', 'Histórico Limpo', 'O histórico de chamadas foi limpo com sucesso.');
    }
}

function renderHistory() {
    const historyContent = document.getElementById('historyContent');

    if (callHistory.length === 0) {
        historyContent.innerHTML = `
            <p class="empty-state">
                <i class="fas fa-inbox"></i>
                Nenhuma chamada realizada ainda
            </p>
        `;
        return;
    }

    let html = '';

    callHistory.forEach((item, index) => {
        const date = new Date(item.timestamp);
        const timeStr = date.toLocaleString('pt-BR');

        const statusClass = item.success ? 'status-success' : 'status-error';
        const statusIcon = item.success ? 'fa-check-circle' : 'fa-times-circle';
        const statusText = item.success ? 'Sucesso' : 'Erro';
        const statusCall = item.call_status == 'registered' ? 'Conectado' : 'Falha';

        html += `
            <div class="history-item">
                <div class="history-header">
                    <h4>
                        ${item.primeiro_nome || 'Cliente'} ${item.sobrenome || ''}
                    </h4>
                    <span class="status-badge ${statusClass}">
                        <i class="fas ${statusIcon}"></i>
                        ${statusText}
                    </span>
                </div>
                <div class="history-details">
                    <div class="history-detail">
                        <i class="fas fa-clock"></i>
                        <span>${timeStr}</span>
                    </div>
                    ${item.to ? `
                        <div class="history-detail">
                            <i class="fas fa-phone"></i>
                            <span>${item.to}</span>
                        </div>
                    ` : ''}
                    ${item.empresa_credora ? `
                        <div class="history-detail">
                            <i class="fas fa-building"></i>
                            <span>${item.empresa_credora}</span>
                        </div>
                    ` : ''}
                    ${item.valor_devido ? `
                        <div class="history-detail">
                            <i class="fas fa-dollar-sign"></i>
                            <span>R$ ${item.valor_devido}</span>
                        </div>
                    ` : ''}
                    ${item.call_id ? `
                        <div class="history-detail">
                            <i class="fas fa-id-card"></i>
                            <span>${statusCall}</span>
                        </div>
                    ` : ''}
                    ${item.error ? `
                        <div class="history-detail" style="grid-column: 1 / -1; color: var(--danger-color);">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>${item.error}</span>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    });

    historyContent.innerHTML = html;
}

// ============================================
// UTILITÁRIOS - SEÇÕES COLAPSÁVEIS
// ============================================
function toggleSection(element) {
    const section = element.closest('.collapsible');
    const content = section.querySelector('.section-content');
    const header = section.querySelector('.section-header');

    if (content.style.display === 'none') {
        content.style.display = 'block';
        header.classList.add('active');
    } else {
        content.style.display = 'none';
        header.classList.remove('active');
    }
}

// ============================================
// FUNÇÕES DE EXEMPLO (PARA TESTES)
// ============================================
function fillExampleData() {
    document.getElementById('primeiro_nome').value = 'Edmilson';
    document.getElementById('sobrenome').value = 'Expedito';
    document.getElementById('to').value = '(12) 99706-1361';
    document.getElementById('cpf').value = '123.456.789-09';
    document.getElementById('data_nascimento').value = '1985-03-15';
    document.getElementById('empresa_credora').value = 'Alfa Serviços';
    document.getElementById('valor_devido').value = '425,00';
    document.getElementById('data_vencimento').value = '2025-09-10';
    document.getElementById('percentual_desconto').value = '10';
    document.getElementById('max_parcelas').value = '3';
    document.getElementById('max_parcelas_estendidas').value = '6';

    calculateValues();
}

// Disponibilizar função globalmente para testes
window.fillExampleData = fillExampleData;

console.log('💡 Dica: Digite fillExampleData() no console para preencher o formulário com dados de exemplo');