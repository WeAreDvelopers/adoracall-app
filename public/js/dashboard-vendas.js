// ============================================
// VARIÁVEIS GLOBAIS
// ============================================
let charts = {};
let currentData = null;
let refreshInterval = null;

// ============================================
// INICIALIZAÇÃO
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    initializeDashboard();

    // Auto-refresh a cada 30 segundos
    refreshInterval = setInterval(refreshDashboard, 30000);
});

async function initializeDashboard() {
    console.log('📊 Inicializando Dashboard de Vendas...');

    // Configurar event listeners
    document.getElementById('filterPeriod').addEventListener('change', refreshDashboard);

    // Carregar dados iniciais
    await loadAnalytics();

    console.log('✅ Dashboard de Vendas inicializado');
}

// ============================================
// CARREGAR ANALYTICS
// ============================================
async function loadAnalytics() {
    try {
        const days = document.getElementById('filterPeriod').value;

        console.log(`📊 Carregando analytics de vendas (${days} dias)...`);

        const response = await fetch(`${API_CONFIG.endpoint.replace('/call/start', '/analytics/sales')}?days=${days}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            }
        });

        if (!response.ok) {
            throw new Error('Erro ao carregar analytics');
        }

        const data = await response.json();

        if (data.success) {
            currentData = data.analytics;
            updateDashboard(currentData);
        } else {
            console.error('❌ Erro:', data.error);
            showError('Erro ao carregar analytics');
        }

    } catch (error) {
        console.error('❌ Erro ao carregar analytics:', error);
        showError('Erro ao conectar com o servidor');
    }
}

// ============================================
// ATUALIZAR DASHBOARD
// ============================================
function updateDashboard(data) {
    console.log('✅ Atualizando dashboard com dados:', data);

    // Atualizar estatísticas principais
    document.getElementById('totalCalls').textContent = formatNumber(data.total_calls);
    document.getElementById('interestedCalls').textContent = formatNumber(data.interested);
    document.getElementById('conversionRate').textContent = data.conversion_rate + '%';
    document.getElementById('followUps').textContent = formatNumber(data.follow_ups?.total_scheduled || 0);

    // Atualizar gráficos
    updateResultsChart(data.by_result);
    updateInterestChart(data.interest_breakdown);
    updateTrendChart(data.daily_trend);
    updateFunnelChart(data.conversion_funnel);

    // Atualizar tabelas
    updateCampaignsTable(data.top_campaigns || []);
    updateProductsTable(data.top_products || []);
}

// ============================================
// GRÁFICOS
// ============================================

function updateResultsChart(byResult) {
    const ctx = document.getElementById('resultsChart');

    if (charts.results) {
        charts.results.destroy();
    }

    const labels = [];
    const values = [];
    const colors = {
        'interessado': '#28a745',
        'nao_interessado': '#dc3545',
        'callback': '#ffc107',
        'sem_resposta': '#6c757d'
    };

    const bgColors = [];

    for (const [key, value] of Object.entries(byResult || {})) {
        labels.push(translateResult(key));
        values.push(value);
        bgColors.push(colors[key] || '#007bff');
    }

    charts.results = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: bgColors,
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

function updateInterestChart(interestBreakdown) {
    const ctx = document.getElementById('interestChart');

    if (charts.interest) {
        charts.interest.destroy();
    }

    const labels = [];
    const values = [];
    const colors = {
        'muito_quente': '#dc3545',
        'quente': '#ff6b6b',
        'morno': '#ffc107',
        'frio': '#17a2b8'
    };

    const bgColors = [];

    for (const [key, value] of Object.entries(interestBreakdown || {})) {
        labels.push(translateInterest(key));
        values.push(value);
        bgColors.push(colors[key] || '#007bff');
    }

    charts.interest = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: bgColors,
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

function updateTrendChart(dailyTrend) {
    const ctx = document.getElementById('trendChart');

    if (charts.trend) {
        charts.trend.destroy();
    }

    const labels = [];
    const totalCalls = [];
    const interested = [];

    (dailyTrend || []).forEach(day => {
        labels.push(new Date(day.date).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' }));
        totalCalls.push(day.total_calls);
        interested.push(day.interested);
    });

    charts.trend = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Total de Chamadas',
                    data: totalCalls,
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    tension: 0.4
                },
                {
                    label: 'Interessados',
                    data: interested,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function updateFunnelChart(funnelData) {
    const ctx = document.getElementById('funnelChart');

    if (charts.funnel) {
        charts.funnel.destroy();
    }

    if (!funnelData) return;

    charts.funnel = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Total Leads', 'Contatados', 'Interessados', 'Follow-up'],
            datasets: [{
                label: 'Quantidade',
                data: [
                    funnelData.total_leads,
                    funnelData.contacted,
                    funnelData.interested,
                    funnelData.follow_up_scheduled
                ],
                backgroundColor: [
                    'rgba(0, 123, 255, 0.8)',
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(40, 167, 69, 0.8)',
                    'rgba(220, 53, 69, 0.8)'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    beginAtZero: true
                }
            }
        }
    });
}

// ============================================
// TABELAS
// ============================================

function updateCampaignsTable(campaigns) {
    const tbody = document.getElementById('campaignsTableBody');

    if (!campaigns || campaigns.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="4" class="empty-cell">
                    <i class="fas fa-inbox"></i>
                    <p>Nenhuma campanha encontrada</p>
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    campaigns.forEach(campaign => {
        // Calcular taxa de conversão (você pode precisar ajustar isso conforme seus dados)
        const conversionRate = '0%'; // TODO: calcular baseado em dados reais

        html += `
            <tr>
                <td><strong>${campaign.campanha}</strong></td>
                <td>${formatNumber(campaign.count)}</td>
                <td>-</td>
                <td>${conversionRate}</td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

function updateProductsTable(products) {
    const tbody = document.getElementById('productsTableBody');

    if (!products || products.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="4" class="empty-cell">
                    <i class="fas fa-inbox"></i>
                    <p>Nenhum produto encontrado</p>
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    products.forEach(product => {
        // Calcular taxa de conversão (você pode precisar ajustar isso conforme seus dados)
        const conversionRate = '0%'; // TODO: calcular baseado em dados reais

        html += `
            <tr>
                <td><strong>${product.produto}</strong></td>
                <td>${formatNumber(product.count)}</td>
                <td>-</td>
                <td>${conversionRate}</td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

// ============================================
// EXPORTAÇÃO
// ============================================

async function exportCSV() {
    try {
        const days = document.getElementById('filterPeriod').value;
        const url = `${API_CONFIG.endpoint.replace('/call/start', '/analytics/export/csv')}?type=sales&days=${days}`;

        window.open(url, '_blank');

        console.log('✅ Exportação CSV iniciada');
    } catch (error) {
        console.error('❌ Erro ao exportar CSV:', error);
        alert('Erro ao exportar CSV');
    }
}

async function exportPDF() {
    try {
        const days = document.getElementById('filterPeriod').value;
        const url = `${API_CONFIG.endpoint.replace('/call/start', '/analytics/export/pdf')}?type=sales&days=${days}`;

        window.open(url, '_blank');

        console.log('✅ Exportação PDF iniciada');
    } catch (error) {
        console.error('❌ Erro ao exportar PDF:', error);
        alert('Erro ao exportar PDF');
    }
}

// ============================================
// UTILITÁRIOS
// ============================================

function refreshDashboard() {
    loadAnalytics();
}

function formatNumber(num) {
    return new Intl.NumberFormat('pt-BR').format(num || 0);
}

function translateResult(result) {
    const translations = {
        'interessado': 'Interessado',
        'nao_interessado': 'Não Interessado',
        'callback': 'Callback',
        'sem_resposta': 'Sem Resposta'
    };
    return translations[result] || result;
}

function translateInterest(interest) {
    const translations = {
        'muito_quente': 'Muito Quente 🔥🔥🔥',
        'quente': 'Quente 🔥🔥',
        'morno': 'Morno 🔥',
        'frio': 'Frio ❄️'
    };
    return translations[interest] || interest;
}

function showError(message) {
    alert(message);
}

// Limpar interval ao sair da página
window.addEventListener('beforeunload', function() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});
