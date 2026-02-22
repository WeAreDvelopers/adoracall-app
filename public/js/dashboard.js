// ============================================
// VARIÁVEIS GLOBAIS
// ============================================
let currentCalls = [];
let currentPage = 1;
let paginationKey = null;
let filters = {
    status: '',
    type: '',
    period: 30
};

let refreshInterval = null;

// ============================================
// INICIALIZAÇÃO
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    initializeDashboard();

    // Auto-refresh a cada 30 segundos
    refreshInterval = setInterval(() => {
        loadStatistics();
        loadCalls();
    }, 30000);
});

// Limpar interval ao sair da página
window.addEventListener('beforeunload', function() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});

function initializeDashboard() {
    // console.log('📊 Inicializando Dashboard...');

    // Configurar event listeners
    setupEventListeners();

    // Carregar dados iniciais
    loadStatistics();
    loadCalls();

    // console.log('✅ Dashboard inicializado');
}

// ============================================
// EVENT LISTENERS
// ============================================
function setupEventListeners() {
    // Mudança no filtro de período
    document.getElementById('filterPeriod').addEventListener('change', function(e) {
        if (e.target.value === 'custom') {
            document.getElementById('customDateFilters').style.display = 'grid';
        } else {
            document.getElementById('customDateFilters').style.display = 'none';
        }
    });
}

// ============================================
// CARREGAR ESTATÍSTICAS
// ============================================
async function loadStatistics() {
    try {
        // console.log('📊 Carregando estatísticas...');

        const response = await fetch(`${API_CONFIG_LOCAL.endpoint.replace('/call/start', '/history/statistics')}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                ...API_CONFIG_LOCAL.headers
            }
        });
        console.log('Estatisticas', response);

        if (!response.ok) {
            throw new Error('Erro ao carregar estatísticas');
        }

        const data = await response.json();

        if (data.success) {
            updateStatistics(data.statistics);
        } else {
            console.error('❌ Erro:', data.error);
        }

    } catch (error) {
        console.error('❌ Erro ao carregar estatísticas:', error);
        // Mostrar valores padrão
        document.getElementById('totalCalls').textContent = '0';
        document.getElementById('successfulCalls').textContent = '0';
        document.getElementById('failedCalls').textContent = '0';
        document.getElementById('avgDuration').textContent = '0s';
    }
}

function updateStatistics(stats) {
    console.log('✅ Estatísticas carregadas:', stats);

    // Total de chamadas
    document.getElementById('totalCalls').textContent = formatNumber(stats.total_calls);

    // Chamadas bem-sucedidas
    document.getElementById('successfulCalls').textContent = formatNumber(stats.successful_calls);

    // Chamadas falhadas
    document.getElementById('failedCalls').textContent = formatNumber(stats.failed_calls);

    // Duração média
    const avgMinutes = Math.floor(stats.average_duration / 60000);
    const avgSeconds = Math.floor((stats.average_duration % 60000) / 1000);
    document.getElementById('avgDuration').textContent = `${avgMinutes}m ${avgSeconds}s`;
}

// ============================================
// CARREGAR CHAMADAS
// ============================================
async function loadCalls(paginationKey = null) {
    try {
        showLoading();

        // console.log('📞 Carregando chamadas...');

        // Preparar body da requisição
        const requestBody = {
            sort_order: 'descending',
            limit: 10
        };

        // Adicionar filtros
        if (filters.status) {
            requestBody.call_status = filters.status;
        }

        if (filters.type) {
            requestBody.call_type = filters.type;
        }

        // Adicionar período
        if (filters.period !== 'custom') {
            const days = parseInt(filters.period);
            requestBody.start_date = new Date(Date.now() - (days * 24 * 60 * 60 * 1000)).toISOString().split('T')[0];
            requestBody.end_date = new Date().toISOString().split('T')[0];
        } else {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            if (startDate && endDate) {
                requestBody.start_date = startDate;
                requestBody.end_date = endDate;
            }
        }

        // Paginação
        if (paginationKey) {
            requestBody.pagination_key = paginationKey;
        }

        const response = await fetch(`${API_CONFIG_LOCAL.endpoint.replace('/call/start', '/history/list')}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                ...API_CONFIG_LOCAL.headers
            },
            body: JSON.stringify(requestBody)
        });

        hideLoading();

        if (!response.ok) {
            const errorText = await response.text();
            console.error('❌ Resposta HTTP não-OK:', response.status, errorText);
            throw new Error(`Erro HTTP ${response.status}: ${errorText}`);
        }

        const data = await response.json();
        // console.log('📦 Dados recebidos:', data);

        if (data.success) {
            console.log('Dados da Chamada', data);
            currentCalls = data.calls || data.data || [];

            if (currentCalls.length === 0) {
                console.warn('⚠️ Nenhuma chamada encontrada no banco de dados');
                showEmptyState('Nenhuma chamada encontrada. Execute "php artisan retell:sync-calls" para sincronizar chamadas da Retell AI.');
            } else {
                // console.log(`✅ ${currentCalls.length} chamadas carregadas`);
                renderCalls(currentCalls);
            }

            // Atualizar paginação
            if (data.has_more) {
                document.getElementById('pagination').style.display = 'flex';
            } else {
                document.getElementById('pagination').style.display = 'none';
            }
        } else {
            // console.error('❌ Erro na resposta:', data.error);
            showEmptyState('Erro: ' + (data.error?.message || 'Erro desconhecido'));
        }

    } catch (error) {
        hideLoading();
        // console.error('❌ Erro ao carregar chamadas:', error);
        showEmptyState('Erro ao conectar com o servidor');
    }
}

function playAudio(audioUrl) {
    // Para parar qualquer áudio anterior
    const existingAudio = document.getElementById('global-audio-player');
    if (existingAudio) {
        existingAudio.pause();
        existingAudio.remove();
    }
    
    // Criar novo player de áudio
    const audio = document.createElement('audio');
    audio.id = 'global-audio-player';
    audio.src = audioUrl;
    audio.controls = true;
    audio.style.position = 'fixed';
    audio.style.bottom = '20px';
    audio.style.right = '20px';
    audio.style.zIndex = '1000';
    
    document.body.appendChild(audio);
    audio.play();
}

// ============================================
// RENDERIZAR CHAMADAS
// ============================================
function renderCalls(calls) {
    // console.log('🎨 renderCalls chamado com', calls.length, 'chamadas');

    const tbody = document.getElementById('callsTableBody');

    if (!tbody) {
        console.error('❌ Elemento callsTableBody não encontrado!');
        return;
    }

    if (!calls || calls.length === 0) {
        console.warn('⚠️ Nenhuma chamada para renderizar');
        showEmptyState();
        return;
    }

    let html = '';

    calls.forEach((call, index) => {
        // console.log(`🔍 Processando chamada ${index + 1}:`, call);
        const date = new Date(call.start_timestamp);
        const dateStr = date.toLocaleString('pt-BR');

        // Usar dados enriquecidos se disponíveis
        const duration = formatDuration(call.enhanced_data?.duration_formatted) || formatDuration(call.call_analysis?.call_summary?.duration_ms || 0);
        const status = call.call_status || 'unknown';
        const type = call.call_type || 'unknown';
        const sentiment = call.user_sentiment || call.enhanced_data?.sentiment || 'Desconhecido';
        sentiment === "Unknown" ? 'Desconhecido' : sentiment;
        const sentimentLower = (sentiment).toLowerCase();

        // Mostrar nome do cliente se disponível, senão número
        const toNumber = call.local_data?.cliente_nome || call.to_number || '-';
        const hasLocalData = !!call.local_data;

        html += `
            <tr>
                <td>
                    ${dateStr}
                    ${hasLocalData ? '<br><small style="color: #999;"><i class="fas fa-database"></i> Dados locais</small>' : ''}
                </td>
                <td>
                    ${toNumber}
                    ${hasLocalData && call.local_data.empresa_credora ? `<br><small style="color: #999;">${call.local_data.empresa_credora}</small>` : ''}
                </td>
                <td>${duration}</td>
                <td>
                    <span class="status-badge status-${status}">
                        ${getStatusIcon(status)} ${translateStatus(status)}
                    </span>
                </td>
                <td>
                    <span class="type-badge">
                        ${getTypeIcon(type)} ${translateType(type)}
                    </span>
                </td>
                <td>
                    <span class="sentiment-badge sentiment-${sentimentLower}">
                        ${sentiment}
                    </span>
                </td>
                <td>
                    <div class="action-btns">
                        <button class="btn-icon" onclick="viewCallDetails('${call.call_id}')" title="Ver Detalhes">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;

}

function showEmptyState(message = 'Nenhuma chamada encontrada') {
    const tbody = document.getElementById('callsTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="7" class="empty-cell">
                <i class="fas fa-inbox"></i>
                <p>${message}</p>
            </td>
        </tr>
    `;
}

// ============================================
// DETALHES DA CHAMADA
// ============================================
async function viewCallDetails(callId) {
    try {
        const modal = document.getElementById('callDetailsModal');
        const modalBody = document.getElementById('callDetailsBody');

        // Mostrar modal com loading
        modal.classList.add('active');
        modalBody.innerHTML = '<div class="spinner"></div><p>Carregando detalhes...</p>';

        // console.log('📞 Buscando detalhes da chamada:', callId);

        const response = await fetch(`${API_CONFIG_LOCAL.endpoint.replace('/call/start', `/history/call/${callId}`)}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                ...API_CONFIG_LOCAL.headers
            }
        });

        if (!response.ok) {
            throw new Error('Erro ao buscar detalhes');
        }

        const data = await response.json();
        console.log('Response', data);

        if (data.success) {
            renderCallDetails(data.data);
        } else {
            modalBody.innerHTML = '<p class="error">Erro ao carregar detalhes da chamada.</p>';
        }

    } catch (error) {
        console.error('❌ Erro ao buscar detalhes:', error);
        document.getElementById('callDetailsBody').innerHTML = '<p class="error">Erro ao conectar com o servidor.</p>';
    }
}

function renderCallDetails(call) {
    // console.log(call);
    const modalBody = document.getElementById('callDetailsBody');

    const startDate = new Date(call.start_timestamp).toLocaleString('pt-BR');
    const endDate = call.end_timestamp ? new Date(call.end_timestamp).toLocaleString('pt-BR') : '-';
    const duration = formatDuration(call.enhanced_data.duration_formatted || 0);

    let html = `
        <div class="detail-section">
            <h3><i class="fas fa-info-circle"></i> Informações Gerais</h3>
            <div class="call-detail-grid">
                <div class="detail-item">
                    <div class="detail-label">ID da Chamada</div>
                    <div class="detail-value">${call.call_id}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Para</div>
                    <div class="detail-value">${call.to_number || '-'}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">De</div>
                    <div class="detail-value">${call.from_number || '-'}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Status</div>
                    <div class="detail-value">
                        <span class="status-badge status-${call.call_status}">
                            ${translateStatus(call.call_status)}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="detail-section">
            <h3><i class="fas fa-clock"></i> Tempo</h3>
            <div class="call-detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Início</div>
                    <div class="detail-value">${startDate}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Fim</div>
                    <div class="detail-value">${endDate}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Duração</div>
                    <div class="detail-value">${duration}</div>
                </div>
            </div>
        </div>
    `;

    // Dados locais do cliente
    if (call.local_data) {
        html += `
            <div class="detail-section">
                <h3><i class="fas fa-database"></i> Dados do Cliente</h3>
                <div class="call-detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Nome</div>
                        <div class="detail-value">${call.local_data.cliente_nome}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Empresa Credora</div>
                        <div class="detail-value">${call.local_data.empresa_credora}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Valor Devido</div>
                        <div class="detail-value">R$ ${call.local_data.valor_devido}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Vencimento</div>
                        <div class="detail-value">${call.local_data.vencimento || '-'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Tentativas</div>
                        <div class="detail-value">${call.local_data.tentativas}</div>
                    </div>
                </div>
            </div>
        `;
    }

    // Análise da chamada
    if (call.call_analysis) {
        html += `
            <div class="detail-section">
                <h3><i class="fas fa-chart-bar"></i> Análise</h3>
                <div class="call-detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Sentimento</div>
                        <div class="detail-value">
                            <span class="sentiment-badge sentiment-${(call.user_sentiment || 'unknown').toLowerCase()}">
                                ${getSentimentIcon(call.user_sentiment)} ${translateSentiment(call.user_sentiment)}
                            </span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Sucesso</div>
                        <div class="detail-value">${call.call_successful ? '✅ Sim' : '❌ Não'}</div>
                    </div>
                </div>
                ${call.call_analysis.call_summary?.summary ? `
                    <div class="detail-item" style="grid-column: 1 / -1;">
                        <div class="detail-label">Resumo da Conversa</div>
                        <div class="detail-value" style="padding: 15px; background: #f5f5f5; border-radius: 8px; line-height: 1.6;">
                            ${call.call_analysis.call_summary.summary}
                        </div>
                    </div>
                ` : ''}
            </div>
        `;
    }

    // Transcrição
    if (call.transcript) {
        html += `
            <div class="detail-section">
                <h3><i class="fas fa-comments"></i> Transcrição</h3>
                <div class="transcript-container" style="max-height: 400px; overflow-y: auto; padding: 15px; background: #f9f9f9; border-radius: 8px; font-family: monospace; white-space: pre-wrap; line-height: 1.8;">
                    ${call.transcript}
                </div>
            </div>
        `;
    }

    // Gravação de áudio
    if (call.recording_url) {
        html += `
            <div class="detail-section">
                <h3><i class="fas fa-headphones"></i> Gravação</h3>
                <audio controls style="width: 100%; margin-top: 10px;">
                    <source src="${call.recording_url}" type="audio/mpeg">
                    Seu navegador não suporta o elemento de áudio.
                </audio>
            </div>
        `;
    }

    // Custo
    if (call.cost || call.enhanced_data?.cost_formatted) {
        html += `
            <div class="detail-section">
                <h3><i class="fas fa-dollar-sign"></i> Custo</h3>
                <div class="call-detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Custo Total</div>
                        <div class="detail-value">${'$' + (call.cost / 100).toFixed(2).replace('.', ',')}</div>
                    </div>
                </div>
            </div>
        `;
    }

    // Metadados
    if (call.metadata && Object.keys(call.metadata).length > 0) {
        html += `
            <div class="detail-section">
                <h3><i class="fas fa-tags"></i> Metadados</h3>
                <div class="call-detail-grid">
        `;

        for (const [key, value] of Object.entries(call.metadata)) {
            html += `
                <div class="detail-item">
                    <div class="detail-label">${key}</div>
                    <div class="detail-value">${value}</div>
                </div>
            `;
        }

        html += `
                </div>
            </div>
        `;
    }

    modalBody.innerHTML = html;
}

function closeCallDetails() {
    document.getElementById('callDetailsModal').classList.remove('active');
}

// ============================================
// FILTROS
// ============================================
function applyFilters() {
    filters.status = document.getElementById('filterStatus').value;
    filters.type = document.getElementById('filterType').value;
    filters.period = document.getElementById('filterPeriod').value;

    // console.log('🔍 Aplicando filtros:', filters);

    currentPage = 1;
    paginationKey = null;
    loadCalls();
}

function clearFilters() {
    document.getElementById('filterStatus').value = '';
    document.getElementById('filterType').value = '';
    document.getElementById('filterPeriod').value = '30';
    document.getElementById('customDateFilters').style.display = 'none';

    filters = {
        status: '',
        type: '',
        period: 30
    };

    loadCalls();
}

function refreshCalls() {
    loadCalls();
    loadStatistics();
}

// ============================================
// PAGINAÇÃO
// ============================================
function nextPage() {
    if (paginationKey) {
        currentPage++;
        loadCalls(paginationKey);
        updatePageInfo();
    }
}

function previousPage() {
    if (currentPage > 1) {
        currentPage--;
        // Note: Retell AI não suporta voltar páginas facilmente
        // Recarregar do início
        paginationKey = null;
        loadCalls();
        updatePageInfo();
    }
}

function updatePageInfo() {
    document.getElementById('pageInfo').textContent = `Página ${currentPage}`;
}

// ============================================
// UTILITÁRIOS
// ============================================
function formatNumber(num) {
    return new Intl.NumberFormat('pt-BR').format(num || 0);
}

function formatDuration(seconds) {
    if (seconds === null || seconds === undefined || isNaN(seconds)) return '-';

    const totalSeconds = Math.max(0, Math.floor(seconds));
    const minutes = Math.floor(totalSeconds / 60);
    const secs = totalSeconds % 60;

    const mm = String(minutes).padStart(2, '0');
    const ss = String(secs).padStart(2, '0');

    return `${mm}:${ss}`;
}


function getStatusIcon(status) {
    const icons = {
        'ended': '<i class="fas fa-check-circle"></i>',
        'in_progress': '<i class="fas fa-spinner fa-spin"></i>',
        'error': '<i class="fas fa-exclamation-circle"></i>',
        'registered': '<i class="fas fa-clock"></i>'
    };
    return icons[status] || '<i class="fas fa-question-circle"></i>';
}

function translateStatus(status) {
    const translations = {
        'ended': 'Finalizada',
        'in_progress': 'Em Andamento',
        'error': 'Erro',
        'registered': 'Registrada',
        'unknown': 'Desconhecido'
    };
    return translations[status] || status;
}

function getTypeIcon(type) {
    const icons = {
        'phone_call': '<i class="fas fa-phone"></i>',
        'web_call': '<i class="fas fa-globe"></i>'
    };
    return icons[type] || '<i class="fas fa-phone"></i>';
}

function translateType(type) {
    const translations = {
        'phone_call': 'Telefone',
        'web_call': 'Web',
        'unknown': 'Desconhecido'
    };
    return translations[type] || type;
}

function getSentimentIcon(sentiment) {
    const icons = {
        'Positive': '<i class="fas fa-smile"></i>',
        'Negative': '<i class="fas fa-frown"></i>',
        'Neutral': '<i class="fas fa-meh"></i>',
        'Indeterminado': '<i class="fas fa-question"></i>'
    };
    return icons[sentiment] || icons['unknown'];
}

function translateSentiment(sentiment) {
    const translations = {
        'Positive': 'Positivo',
        'Negative': 'Negativo',
        'Neutral': 'Neutro',
        'Indeterminado': 'Desconhecido'
    };
    return translations[sentiment] || sentiment;
}

function showLoading() {
    const tbody = document.getElementById('callsTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="7" class="loading-cell">
                <div class="spinner"></div>
                <p>Carregando chamadas...</p>
            </td>
        </tr>
    `;
}

function hideLoading() {
    // Loading é substituído pelo conteúdo
}
