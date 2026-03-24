@extends('layouts.app')

@section('title', 'Dashboard Acordos - AdoraCall')

@section('content')
<div class="dashboard-wrapper">
    <!-- Dashboard Header -->
    <div class="dashboard-header-section">
        <div class="dashboard-header-content">
            <div class="header-left">
                <h1 class="dashboard-title">
                    <i class="fas fa-chart-pie"></i> Acordos & Conversão
                </h1>
                <p class="dashboard-subtitle">Acompanhe métricas consolidadas de ligações e acordos</p>
            </div>

            <div class="header-right">
                <div class="period-filter">
                    <button class="filter-btn active" data-days="7">7 dias</button>
                    <button class="filter-btn" data-days="30">30 dias</button>
                    <button class="filter-btn" data-days="60">60 dias</button>
                    <button class="filter-btn" data-days="90">90 dias</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div id="loadingState" class="loading-state">
        <div class="spinner">
            <i class="fas fa-spinner"></i>
        </div>
        <p>Carregando dados...</p>
    </div>

    <!-- KPI Cards Grid -->
    <div id="kpiGrid" class="kpi-grid" style="display: none;">
        <!-- Ligações Atendidas -->
        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-icon ligacoes">📞</span>
                <span class="kpi-badge">Atendidas</span>
            </div>
            <div class="kpi-body">
                <div class="kpi-value" id="ligacoesAtendidas">0</div>
                <div class="kpi-meta">
                    <span class="kpi-label">de</span>
                    <span class="kpi-total" id="ligacoesTotais">0</span>
                    <span class="kpi-label">tentativas</span>
                </div>
                <div class="kpi-percent" id="taxaAtendimento">0%</div>
            </div>
        </div>

        <!-- Acordos Firmados -->
        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-icon acordos">✅</span>
                <span class="kpi-badge">Acordos</span>
            </div>
            <div class="kpi-body">
                <div class="kpi-value" id="acordosFirmados">0</div>
                <div class="kpi-meta">
                    <span class="kpi-label">acordos realizados</span>
                </div>
                <div class="kpi-percent" id="taxaConversao">0%</div>
            </div>
        </div>

        <!-- Taxa de Conversão -->
        <div class="kpi-card highlight">
            <div class="kpi-header">
                <span class="kpi-icon conversao">🎯</span>
                <span class="kpi-badge">Taxa</span>
            </div>
            <div class="kpi-body">
                <div class="kpi-value" id="taxaConversaoValue">0%</div>
                <div class="kpi-meta">
                    <span class="kpi-label" id="metricaConversao">0 de 0</span>
                </div>
                <div class="kpi-chart" id="sparkline"></div>
            </div>
        </div>

        <!-- Valor Acordado -->
        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-icon valor">💰</span>
                <span class="kpi-badge">Valor</span>
            </div>
            <div class="kpi-body">
                <div class="kpi-value" id="valorAcordado">R$ 0</div>
                <div class="kpi-meta">
                    <span class="kpi-label">total acordado</span>
                </div>
                <div class="kpi-percent" id="valorMedio">R$ 0/acordo</div>
            </div>
        </div>

        <!-- Duração Média -->
        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-icon duracao">⏱️</span>
                <span class="kpi-badge">Duração</span>
            </div>
            <div class="kpi-body">
                <div class="kpi-value" id="duracaoMedia">0s</div>
                <div class="kpi-meta">
                    <span class="kpi-label">duração média</span>
                </div>
                <div class="kpi-percent" id="duracaoTotal">Total: 0h</div>
            </div>
        </div>

        <!-- Desconto Concedido -->
        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-icon desconto">🏷️</span>
                <span class="kpi-badge">Desconto</span>
            </div>
            <div class="kpi-body">
                <div class="kpi-value" id="descontoTotal">R$ 0</div>
                <div class="kpi-meta">
                    <span class="kpi-label">desconto oferecido</span>
                </div>
                <div class="kpi-percent" id="descontoPercent">0%</div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div id="chartsSection" class="charts-section" style="display: none;">
        <!-- Charts Grid -->
        <div class="charts-grid">
            <!-- Conversão Chart -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3>Taxa de Conversão</h3>
                    <span class="chart-subtitle">Acordos vs Ligações Atendidas</span>
                </div>
                <div class="chart-container">
                    <canvas id="conversionChart"></canvas>
                </div>
            </div>

            <!-- Acordos por Tipo -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3>Acordos por Tipo</h3>
                    <span class="chart-subtitle">Distribuição de parcelas</span>
                </div>
                <div class="chart-container">
                    <canvas id="typesChart"></canvas>
                </div>
            </div>

            <!-- Acordos por Status -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3>Status dos Acordos</h3>
                    <span class="chart-subtitle">Estado atual</span>
                </div>
                <div class="chart-container">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>

            <!-- Timeline -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3>Timeline de Acordos</h3>
                    <span class="chart-subtitle">Evolução ao longo do período</span>
                </div>
                <div class="chart-container">
                    <canvas id="timelineChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Últimos Acordos Table -->
    <div id="tableSection" class="table-section" style="display: none;">
        <div class="section-header">
            <h2>Últimos Acordos</h2>
            <span class="section-badge">Top 10</span>
        </div>

        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>CPF</th>
                        <th>Valor</th>
                        <th>Parcelas</th>
                        <th>Status</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody id="ultimosAcordosTable">
                    <tr class="empty-row">
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <p>Nenhum acordo encontrado</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .dashboard-wrapper {
        max-width: 1600px;
        margin: 0 auto;
        animation: fadeIn 0.5s ease;
    }

    .dashboard-header-section {
        margin-bottom: var(--notion-spacing-2xl);
        background: linear-gradient(135deg, #f5f7fa 0%, #ffffff 100%);
        padding: var(--notion-spacing-2xl);
        border-radius: var(--notion-radius-lg);
        border: 1px solid var(--notion-divider);
    }

    .dashboard-header-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--notion-spacing-xl);
        flex-wrap: wrap;
    }

    .header-left {
        flex: 1;
        min-width: 300px;
    }

    .dashboard-title {
        font-size: var(--notion-font-size-2xl);
        font-weight: 700;
        color: var(--notion-text);
        margin-bottom: var(--notion-spacing-sm);
        display: flex;
        align-items: center;
        gap: var(--notion-spacing-md);
    }

    .dashboard-subtitle {
        color: var(--notion-text-secondary);
        font-size: var(--notion-font-size-base);
        margin: 0;
    }

    .header-right {
        display: flex;
        align-items: center;
        gap: var(--notion-spacing-md);
    }

    .period-filter {
        display: flex;
        gap: var(--notion-spacing-sm);
        background: white;
        padding: var(--notion-spacing-sm);
        border-radius: var(--notion-radius-md);
        border: 1px solid var(--notion-divider);
    }

    .filter-btn {
        padding: var(--notion-spacing-sm) var(--notion-spacing-md);
        border: none;
        background: transparent;
        color: var(--notion-text-secondary);
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        border-radius: var(--notion-radius-sm);
        transition: all 0.2s ease;
    }

    .filter-btn:hover {
        background: var(--notion-bg-secondary);
        color: var(--notion-text);
    }

    .filter-btn.active {
        background: var(--notion-primary);
        color: white;
    }

    .loading-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 400px;
        gap: var(--notion-spacing-lg);
        color: var(--notion-text-secondary);
    }

    .spinner {
        font-size: 40px;
        animation: spin 2s linear infinite;
    }

    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: var(--notion-spacing-lg);
        margin-bottom: var(--notion-spacing-2xl);
    }

    .kpi-card {
        background: white;
        border: 1px solid var(--notion-divider);
        border-radius: var(--notion-radius-lg);
        padding: var(--notion-spacing-xl);
        display: flex;
        flex-direction: column;
        gap: var(--notion-spacing-md);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .kpi-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #667eea, #764ba2);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .kpi-card:hover {
        border-color: var(--notion-primary);
        box-shadow: var(--notion-shadow-lg);
    }

    .kpi-card:hover::before {
        opacity: 1;
    }

    .kpi-card.highlight {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-color: transparent;
    }

    .kpi-card.highlight .kpi-label {
        color: rgba(255, 255, 255, 0.8);
    }

    .kpi-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .kpi-icon {
        font-size: 28px;
        opacity: 0.8;
    }

    .kpi-badge {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--notion-text-secondary);
    }

    .kpi-card.highlight .kpi-badge {
        color: rgba(255, 255, 255, 0.8);
    }

    .kpi-body {
        display: flex;
        flex-direction: column;
        gap: var(--notion-spacing-md);
    }

    .kpi-value {
        font-size: var(--notion-font-size-2xl);
        font-weight: 700;
        color: var(--notion-text);
    }

    .kpi-card.highlight .kpi-value {
        color: white;
    }

    .kpi-meta {
        display: flex;
        align-items: center;
        gap: var(--notion-spacing-sm);
        flex-wrap: wrap;
        font-size: 13px;
    }

    .kpi-label {
        color: var(--notion-text-secondary);
    }

    .kpi-total {
        font-weight: 600;
        color: var(--notion-text);
    }

    .kpi-percent {
        font-size: 13px;
        color: var(--notion-success);
        font-weight: 600;
    }

    .charts-section {
        margin-bottom: var(--notion-spacing-2xl);
    }

    .charts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: var(--notion-spacing-lg);
        margin-bottom: var(--notion-spacing-2xl);
    }

    .chart-card {
        background: white;
        border: 1px solid var(--notion-divider);
        border-radius: var(--notion-radius-lg);
        padding: var(--notion-spacing-xl);
        display: flex;
        flex-direction: column;
        transition: all 0.2s ease;
    }

    .chart-card:hover {
        box-shadow: var(--notion-shadow-md);
        border-color: var(--notion-primary);
    }

    .chart-header {
        display: flex;
        flex-direction: column;
        gap: var(--notion-spacing-xs);
        margin-bottom: var(--notion-spacing-lg);
        padding-bottom: var(--notion-spacing-lg);
        border-bottom: 1px solid var(--notion-divider);
    }

    .chart-header h3 {
        font-size: var(--notion-font-size-lg);
        color: var(--notion-text);
        margin: 0;
    }

    .chart-subtitle {
        font-size: 13px;
        color: var(--notion-text-secondary);
    }

    .chart-container {
        position: relative;
        min-height: 300px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .table-section {
        background: white;
        border: 1px solid var(--notion-divider);
        border-radius: var(--notion-radius-lg);
        padding: var(--notion-spacing-xl);
    }

    .section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: var(--notion-spacing-lg);
        padding-bottom: var(--notion-spacing-lg);
        border-bottom: 1px solid var(--notion-divider);
    }

    .section-header h2 {
        font-size: var(--notion-font-size-lg);
        color: var(--notion-text);
        margin: 0;
    }

    .section-badge {
        font-size: 11px;
        font-weight: 700;
        background: var(--notion-primary-light);
        color: var(--notion-primary);
        padding: var(--notion-spacing-xs) var(--notion-spacing-sm);
        border-radius: var(--notion-radius-sm);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .table-wrapper {
        overflow-x: auto;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }

    .data-table thead {
        background: var(--notion-bg-secondary);
    }

    .data-table th {
        padding: var(--notion-spacing-md) var(--notion-spacing-lg);
        text-align: left;
        font-weight: 600;
        color: var(--notion-text);
        border-bottom: 2px solid var(--notion-divider);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 11px;
    }

    .data-table td {
        padding: var(--notion-spacing-md) var(--notion-spacing-lg);
        border-bottom: 1px solid var(--notion-divider);
        color: var(--notion-text);
    }

    .data-table tbody tr:hover {
        background: var(--notion-bg-secondary);
    }

    .data-table tbody tr:last-child td {
        border-bottom: none;
    }

    .status-badge {
        display: inline-block;
        padding: var(--notion-spacing-xs) var(--notion-spacing-sm);
        border-radius: var(--notion-radius-sm);
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .status-pendente {
        background: var(--notion-warning-light);
        color: var(--notion-warning);
    }

    .status-pago {
        background: var(--notion-success-light);
        color: var(--notion-success);
    }

    .status-vencido {
        background: var(--notion-danger-light);
        color: var(--notion-danger);
    }

    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: var(--notion-spacing-md);
        padding: var(--notion-spacing-2xl) var(--notion-spacing-lg);
        color: var(--notion-text-tertiary);
    }

    .empty-state i {
        font-size: 40px;
        opacity: 0.3;
    }

    .empty-row td {
        padding: var(--notion-spacing-2xl) !important;
    }

    @media (max-width: 768px) {
        .dashboard-header-content {
            flex-direction: column;
            align-items: flex-start;
        }

        .kpi-grid {
            grid-template-columns: 1fr;
        }

        .charts-grid {
            grid-template-columns: 1fr;
        }

        .period-filter {
            width: 100%;
            flex-wrap: wrap;
        }

        .data-table {
            font-size: 12px;
        }

        .data-table th,
        .data-table td {
            padding: var(--notion-spacing-sm) var(--notion-spacing-md);
        }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
<script>
    const API_BASE_URL = '{{ config("app.api_url") ?? url("/api") }}';
    let currentDays = 30;
    let charts = {};

    document.addEventListener('DOMContentLoaded', function() {
        loadDashboardData();

        // Period filter buttons
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                currentDays = parseInt(this.dataset.days);
                loadDashboardData();
            });
        });
    });

    async function loadDashboardData() {
        try {
            const token = localStorage.getItem('api_token');

            // Fetch all data in parallel
            const [consolidatedRes, agreementsRes, timelineRes] = await Promise.all([
                fetch(`${API_BASE_URL}/dashboard/consolidated?days=${currentDays}`, {
                    headers: { 'Authorization': `Bearer ${token}` }
                }),
                fetch(`${API_BASE_URL}/dashboard/agreements?days=${currentDays}`, {
                    headers: { 'Authorization': `Bearer ${token}` }
                }),
                fetch(`${API_BASE_URL}/dashboard/timeline?days=${currentDays}`, {
                    headers: { 'Authorization': `Bearer ${token}` }
                })
            ]);

            const consolidated = await consolidatedRes.json();
            const agreements = await agreementsRes.json();
            const timeline = await timelineRes.json();

            updateKPIs(consolidated.data);
            updateCharts(consolidated.data, agreements.data, timeline.data);
            updateTable(agreements.data.ultimos_acordos);

            document.getElementById('loadingState').style.display = 'none';
            document.getElementById('kpiGrid').style.display = 'grid';
            document.getElementById('chartsSection').style.display = 'block';
            document.getElementById('tableSection').style.display = 'block';

        } catch (error) {
            console.error('Erro ao carregar dados:', error);
            document.getElementById('loadingState').innerHTML = `
                <div style="text-align: center; color: var(--notion-danger);">
                    <i class="fas fa-exclamation-triangle" style="font-size: 40px; margin-bottom: 1rem;"></i>
                    <p>Erro ao carregar dados</p>
                </div>
            `;
        }
    }

    function updateKPIs(data) {
        const ligacoes = data.ligacoes;
        const acordos = data.acordos;
        const conversao = data.conversao;
        const valores = data.valor_consolidado;

        // Ligações Atendidas
        document.getElementById('ligacoesAtendidas').textContent = ligacoes.total_atendidas;
        document.getElementById('ligacoesTotais').textContent = ligacoes.total_tentativas;
        document.getElementById('taxaAtendimento').textContent = ligacoes.taxa_atendimento + '%';

        // Acordos
        document.getElementById('acordosFirmados').textContent = acordos.total_acordos;
        document.getElementById('taxaConversao').textContent = conversao.taxa_conversao_pct + '%';

        // Conversão
        document.getElementById('taxaConversaoValue').textContent = conversao.taxa_conversao_pct + '%';
        document.getElementById('metricaConversao').textContent = conversao.acordos_confirmados + ' de ' + conversao.ligacoes_atendidas;

        // Valor
        document.getElementById('valorAcordado').textContent = 'R$ ' + formatNumber(acordos.valor_total);
        document.getElementById('valorMedio').textContent = 'R$ ' + formatNumber(acordos.valor_medio) + '/acordo';

        // Duração
        const duracaoMedia = Math.round(ligacoes.duracao_media / 60);
        const duracaoTotalMinutos = Math.round(ligacoes.duracao_total / 60);
        const duracaoHoras = Math.round(duracaoTotalMinutos / 60);
        document.getElementById('duracaoMedia').textContent = duracaoMedia + 's';
        document.getElementById('duracaoTotal').textContent = 'Total: ' + duracaoHoras + 'h';

        // Desconto
        document.getElementById('descontoTotal').textContent = 'R$ ' + formatNumber(valores.desconto_concedido);
        const descontoPercent = acordos.valor_total > 0 ? ((valores.desconto_concedido / acordos.valor_total) * 100).toFixed(1) : 0;
        document.getElementById('descontoPercent').textContent = descontoPercent + '%';
    }

    function updateCharts(consolidated, agreements, timeline) {
        // Conversão Chart
        updateConversionChart(consolidated.conversao);

        // Tipos Chart
        updateTypesChart(agreements.por_tipo);

        // Status Chart
        updateStatusChart(agreements.por_status);

        // Timeline Chart
        updateTimelineChart(agreements.timeline);
    }

    function updateConversionChart(conversao) {
        const ctx = document.getElementById('conversionChart').getContext('2d');
        if (charts.conversion) charts.conversion.destroy();

        charts.conversion = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Acordos', 'Sem Acordo'],
                datasets: [{
                    data: [
                        conversao.acordos_confirmados,
                        conversao.ligacoes_atendidas - conversao.acordos_confirmados
                    ],
                    backgroundColor: ['#667eea', '#e5e3e0'],
                    borderColor: white,
                    borderWidth: 2,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { font: { size: 12 }, padding: 16 }
                    }
                }
            }
        });
    }

    function updateTypesChart(tipos) {
        const ctx = document.getElementById('typesChart').getContext('2d');
        if (charts.types) charts.types.destroy();

        charts.types = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: tipos.map(t => t.tipo),
                datasets: [{
                    label: 'Quantidade',
                    data: tipos.map(t => t.quantidade),
                    backgroundColor: '#667eea',
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, border: { display: false }, grid: { color: 'rgba(0, 0, 0, 0.05)' } }
                }
            }
        });
    }

    function updateStatusChart(status) {
        const ctx = document.getElementById('statusChart').getContext('2d');
        if (charts.status) charts.status.destroy();

        charts.status = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: status.map(s => s.status),
                datasets: [{
                    data: status.map(s => s.total),
                    backgroundColor: ['#FFAE00', '#10b981', '#ef4444'],
                    borderColor: 'white',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { font: { size: 12 }, padding: 16 }
                    }
                }
            }
        });
    }

    function updateTimelineChart(timeline) {
        const ctx = document.getElementById('timelineChart').getContext('2d');
        if (charts.timeline) charts.timeline.destroy();

        timeline.reverse();

        charts.timeline = new Chart(ctx, {
            type: 'line',
            data: {
                labels: timeline.map(t => t.data),
                datasets: [{
                    label: 'Acordos',
                    data: timeline.map(t => t.quantidade),
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2,
                    pointRadius: 4,
                    pointBackgroundColor: '#667eea',
                    pointBorderColor: 'white',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, border: { display: false }, grid: { color: 'rgba(0, 0, 0, 0.05)' } }
                }
            }
        });
    }

    function updateTable(acordos) {
        const tbody = document.getElementById('ultimosAcordosTable');

        if (!acordos || acordos.length === 0) {
            tbody.innerHTML = `
                <tr class="empty-row">
                    <td colspan="6">
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>Nenhum acordo encontrado</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = acordos.map(acordo => `
            <tr>
                <td><strong>${maskString(acordo.nome, 15)}</strong></td>
                <td>${maskCPF(acordo.cpf)}</td>
                <td>${acordo.valor}</td>
                <td>${acordo.parcelas}x</td>
                <td><span class="status-badge status-${acordo.status}">${formatStatus(acordo.status)}</span></td>
                <td>${acordo.data}</td>
            </tr>
        `).join('');
    }

    function formatNumber(num) {
        return parseFloat(num).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function maskCPF(cpf) {
        return cpf ? cpf.substring(0, 3) + '***' + cpf.substring(cpf.length - 3) : 'N/A';
    }

    function maskString(str, len) {
        return str && str.length > len ? str.substring(0, len) + '...' : str;
    }

    function formatStatus(status) {
        const map = {
            'pendente_pagamento': 'Pendente',
            'pago': 'Pago',
            'vencido': 'Vencido',
            'concluido': 'Concluído'
        };
        return map[status] || status;
    }
</script>
@endsection
