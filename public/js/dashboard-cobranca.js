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
    console.log('📊 Inicializando Dashboard de Cobrança...');

    // Configurar event listeners
    document.getElementById('filterPeriod').addEventListener('change', refreshDashboard);

    // Carregar dados iniciais
    await loadAnalytics();

    console.log('✅ Dashboard de Cobrança inicializado');
}

// ============================================
// CARREGAR ANALYTICS
// ============================================
async function loadAnalytics() {
    try {
        const days = document.getElementById('filterPeriod').value;

        console.log(`📊 Carregando analytics de cobrança (${days} dias)...`);

        const response = await fetch(`${API_CONFIG.endpoint.replace('/call/start', '/analytics/collection')}?days=${days}`, {
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
    document.getElementById('validationSuccess').textContent = formatNumber(data.validation_success);
    document.getElementById('validationRate').textContent = data.validation_rate + '%';

    // Análise de dívidas
    if (data.debt_analysis) {
        document.getElementById('totalDebt').textContent = formatMoney(data.debt_analysis.contacted_debt);
        document.getElementById('debtTotal').textContent = formatMoney(data.debt_analysis.total_debt);
        document.getElementById('debtContacted').textContent = formatMoney(data.debt_analysis.contacted_debt);
        document.getElementById('debtResolved').textContent = formatMoney(data.debt_analysis.resolved_debt);
        document.getElementById('resolutionRate').textContent = data.debt_analysis.resolution_rate + '%';
    }

    // Atualizar gráficos
    updateStatusChart(data.by_status);
    updateValidationChart(data);
    updateTrendChart(data.daily_trend);
    updateAttemptsChart(data.success_rate_by_attempts);
}

// ============================================
// GRÁFICOS
// ============================================

function updateStatusChart(byStatus) {
    const ctx = document.getElementById('statusChart');

    if (charts.status) {
        charts.status.destroy();
    }

    const labels = [];
    const values = [];
    const colors = {
        'concluida': '#28a745',
        'em_andamento': '#ffc107',
        'iniciada': '#17a2b8',
        'falhada': '#dc3545'
    };

    const bgColors = [];

    for (const [key, value] of Object.entries(byStatus || {})) {
        labels.push(translateStatus(key));
        values.push(value);
        bgColors.push(colors[key] || '#007bff');
    }

    charts.status = new Chart(ctx, {
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

function updateValidationChart(data) {
    const ctx = document.getElementById('validationChart');

    if (charts.validation) {
        charts.validation.destroy();
    }

    charts.validation = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Validações Bem-Sucedidas', 'Validações Falhadas'],
            datasets: [{
                data: [data.validation_success || 0, data.validation_failed || 0],
                backgroundColor: ['#28a745', '#dc3545'],
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
    const successfulValidations = [];

    (dailyTrend || []).forEach(day => {
        labels.push(new Date(day.date).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' }));
        totalCalls.push(day.total_calls);
        successfulValidations.push(day.successful_validations);
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
                    label: 'Validações Bem-Sucedidas',
                    data: successfulValidations,
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

function updateAttemptsChart(successRateByAttempts) {
    const ctx = document.getElementById('attemptsChart');

    if (charts.attempts) {
        charts.attempts.destroy();
    }

    const labels = [];
    const successRates = [];

    (successRateByAttempts || []).forEach(item => {
        labels.push(`${item.attempts} tentativa${item.attempts > 1 ? 's' : ''}`);
        successRates.push(item.success_rate);
    });

    charts.attempts = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Taxa de Sucesso (%)',
                data: successRates,
                backgroundColor: 'rgba(0, 123, 255, 0.8)',
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });
}

// ============================================
// EXPORTAÇÃO
// ============================================

async function exportCSV() {
    try {
        const days = document.getElementById('filterPeriod').value;
        const url = `${API_CONFIG.endpoint.replace('/call/start', '/analytics/export/csv')}?type=collection&days=${days}`;

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
        const url = `${API_CONFIG.endpoint.replace('/call/start', '/analytics/export/pdf')}?type=collection&days=${days}`;

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

function formatMoney(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value || 0);
}

function translateStatus(status) {
    const translations = {
        'concluida': 'Concluída',
        'em_andamento': 'Em Andamento',
        'iniciada': 'Iniciada',
        'falhada': 'Falhada'
    };
    return translations[status] || status;
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
