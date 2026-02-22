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

    console.log('✅ Sistema de Vendas inicializado');
}

// ============================================
// EVENT LISTENERS
// ============================================
function setupEventListeners() {
    const form = document.getElementById('salesForm');
    form.addEventListener('submit', handleFormSubmit);

    // Formatação automática de telefone
    const phoneInput = document.getElementById('to');
    phoneInput.addEventListener('input', formatPhoneNumber);
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
    const form = document.getElementById('salesForm');
    const formData = new FormData(form);
    const data = {};

    for (let [key, value] of formData.entries()) {
        if (value.trim() !== '') {
            data[key] = value;
        }
    }

    // Formatar telefone para formato internacional
    data.to = formatPhoneToInternational(data.to);

    // Adicionar tipo de chamada
    data.tipo = 'vendas';

    return data;
}

function validateFormData(data) {
    const required = ['to', 'primeiro_nome', 'produto'];

    for (let field of required) {
        if (!data[field]) {
            showAlert('error', 'Erro de Validação', `O campo "${getFieldLabel(field)}" é obrigatório.`);
            return false;
        }
    }

    return true;
}

function getFieldLabel(fieldName) {
    const labels = {
        'to': 'Telefone',
        'primeiro_nome': 'Primeiro Nome',
        'produto': 'Produto/Serviço'
    };
    return labels[fieldName] || fieldName;
}

function resetForm() {
    document.getElementById('salesForm').reset();
    hideStatus();
}

// ============================================
// REQUISIÇÃO API
// ============================================
async function sendCallRequest(data) {
    showLoading();

    try {
        console.log('📤 Enviando requisição de vendas:', data);

        // TODO: Atualizar endpoint quando backend estiver pronto
        const endpoint = API_CONFIG_LOCAL.endpoint.replace('/call/start', '/call/vendas');

        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                ...API_CONFIG_LOCAL.headers
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        hideLoading();

        if (response.ok && result.success) {
            handleSuccess(result, data);
        } else {
            handleError(result);
        }

    } catch (error) {
        hideLoading();
        console.error('❌ Erro na requisição:', error);
        showAlert('error', 'Erro de Conexão', `Não foi possível conectar ao servidor: ${error.message}`);
    }
}

function handleSuccess(result, originalData) {
    console.log('✅ Chamada de vendas iniciada com sucesso:', result);

    // Mostrar status de sucesso
    showStatus('success', {
        title: 'Chamada de Vendas Iniciada com Sucesso!',
        callId: result.call_id,
        status: result.call_status,
        agentId: result.agent_id,
        message: result.message || 'O prospect receberá a ligação em breve.'
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

    const errorMessage = error.error?.message || error.message || 'Erro desconhecido ao iniciar chamada de vendas.';

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
        localStorage.setItem('salesCallHistory', JSON.stringify(callHistory));
    } catch (error) {
        console.error('Erro ao salvar histórico:', error);
    }
}

function loadHistory() {
    try {
        const saved = localStorage.getItem('salesCallHistory');
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
    if (confirm('Tem certeza que deseja limpar todo o histórico de vendas?')) {
        callHistory = [];
        saveHistory();
        renderHistory();
        showAlert('info', 'Histórico Limpo', 'O histórico de chamadas de vendas foi limpo com sucesso.');
    }
}

function renderHistory() {
    const historyContent = document.getElementById('historyContent');

    if (callHistory.length === 0) {
        historyContent.innerHTML = `
            <p class="empty-state">
                <i class="fas fa-inbox"></i>
                Nenhuma chamada de vendas realizada ainda
            </p>
        `;
        return;
    }

    let html = '';

    callHistory.forEach((item, index) => {
        console.log('Item', item);
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
                        ${item.primeiro_nome || 'Prospect'} ${item.sobrenome || ''}
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
                    ${item.produto ? `
                        <div class="history-detail">
                            <i class="fas fa-shopping-cart"></i>
                            <span>${item.produto}</span>
                        </div>
                    ` : ''}
                    ${item.email ? `
                        <div class="history-detail">
                            <i class="fas fa-envelope"></i>
                            <span>${item.email}</span>
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
    document.getElementById('primeiro_nome').value = 'Maria';
    document.getElementById('sobrenome').value = 'Santos';
    document.getElementById('to').value = '(11) 98765-4321';
    document.getElementById('email').value = 'maria.santos@example.com';
    document.getElementById('produto').value = 'plano_premium';
    document.getElementById('origem').value = 'site';
    document.getElementById('observacoes').value = 'Prospect demonstrou interesse em plano premium durante webinar.';
    document.getElementById('campanha').value = 'Webinar Q1 2025';
    document.getElementById('tag').value = 'quente, webinar, premium';
}

// Disponibilizar função globalmente para testes
window.fillExampleData = fillExampleData;

console.log('💡 Dica: Digite fillExampleData() no console para preencher o formulário com dados de exemplo');
