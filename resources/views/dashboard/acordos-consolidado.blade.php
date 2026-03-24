@extends('layouts.app')

@section('title', 'Dashboard Acordos - AdoraCall')

@section('content')
    <style>
        .dashboard-container {
            padding: 2rem;
            max-width: 100%;
        }

        .dashboard-header {
            margin-bottom: 2rem;
        }

        .dashboard-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: var(--notion-text);
        }

        .dashboard-header p {
            color: var(--notion-text-secondary);
            font-size: 1rem;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .kpi-card {
            background: var(--notion-bg);
            border-radius: 8px;
            padding: 1.5rem;
            border: 1px solid var(--notion-border);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .kpi-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }

        .kpi-label {
            font-size: 0.9rem;
            color: var(--notion-text-secondary);
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }

        .kpi-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--notion-text);
            margin-bottom: 0.5rem;
        }

        .kpi-subtitle {
            font-size: 0.85rem;
            color: var(--notion-text-secondary);
        }

        .kpi-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        .status-badge {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            white-space: nowrap;
        }

        .status-sucesso {
            background: #c8e6c9;
            color: #1b5e20;
        }

        .status-pendente {
            background: #fff9c4;
            color: #f57f17;
        }

        .status-vencido {
            background: #ffcdd2;
            color: #b71c1c;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .chart-card {
            background: var(--notion-bg);
            border-radius: 8px;
            padding: 1.5rem;
            border: 1px solid var(--notion-border);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .chart-card h3 {
            margin-top: 0;
            margin-bottom: 1.5rem;
            color: var(--notion-text);
            font-size: 1.1rem;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        .table-card {
            background: var(--notion-bg);
            border-radius: 8px;
            border: 1px solid var(--notion-border);
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .table-card h3 {
            padding: 1.5rem;
            margin: 0;
            border-bottom: 1px solid var(--notion-border);
            color: var(--notion-text);
            font-size: 1.1rem;
        }

        .table-container {
            overflow-x: auto;
        }

        .notion-table {
            width: 100%;
            border-collapse: collapse;
        }

        .notion-table thead {
            background: var(--notion-bg-secondary);
            border-bottom: 2px solid var(--notion-border);
        }

        .notion-table th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--notion-text);
            font-size: 0.9rem;
        }

        .notion-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--notion-border);
            color: var(--notion-text);
        }

        .notion-table tbody tr:hover {
            background: var(--notion-bg-secondary);
        }

        .filters-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-group label {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--notion-text);
        }

        .filter-group select,
        .filter-group input {
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--notion-border);
            border-radius: 4px;
            background: var(--notion-bg);
            color: var(--notion-text);
            font-size: 0.9rem;
        }

        .btn-refresh {
            padding: 0.5rem 1rem;
            background: var(--dvelopers-gold);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-refresh:hover {
            background: darken(var(--dvelopers-gold), 10%);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .loading {
            text-align: center;
            padding: 2rem;
            color: var(--notion-text-secondary);
        }

        .spinner {
            border: 4px solid var(--notion-border);
            border-top: 4px solid var(--dvelopers-gold);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .metric-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--notion-border);
        }

        .metric-row:last-child {
            border-bottom: none;
        }

        .metric-label {
            font-size: 0.9rem;
            color: var(--notion-text-secondary);
        }

        .metric-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--notion-text);
        }
    </style>

    <div class="dashboard-container">
        <!-- Header -->
        <div class="dashboard-header">
            <h1><i class="fas fa-chart-bar"></i> Dashboard Consolidado</h1>
            <p>Ligações, Acordos e Taxa de Conversão</p>
        </div>

        <!-- Filtros -->
        <div class="filters-bar">
            <div class="filter-group">
                <label>Período (dias)</label>
                <select id="diasFilter" onchange="recarregarDados()">
                    <option value="7">Últimos 7 dias</option>
                    <option value="30" selected>Últimos 30 dias</option>
                    <option value="60">Últimos 60 dias</option>
                    <option value="90">Últimos 90 dias</option>
                </select>
            </div>
            <button class="btn-refresh" onclick="recarregarDados()">
                <i class="fas fa-sync-alt"></i> Atualizar
            </button>
        </div>

        <!-- KPI Cards -->
        <div id="kpiContainer" class="kpi-grid">
            <div class="loading">
                <div class="spinner"></div>
                Carregando dados...
            </div>
        </div>

        <!-- Gráficos -->
        <div class="charts-grid">
            <!-- Taxa de Conversão -->
            <div class="chart-card">
                <h3><i class="fas fa-chart-pie"></i> Taxa de Conversão</h3>
                <div class="chart-container">
                    <canvas id="conversaoChart"></canvas>
                </div>
            </div>

            <!-- Acordos por Tipo -->
            <div class="chart-card">
                <h3><i class="fas fa-chart-bar"></i> Acordos por Tipo</h3>
                <div class="chart-container">
                    <canvas id="acordosTipoChart"></canvas>
                </div>
            </div>

            <!-- Acordos por Status -->
            <div class="chart-card">
                <h3><i class="fas fa-tasks"></i> Acordos por Status</h3>
                <div class="chart-container">
                    <canvas id="acordosStatusChart"></canvas>
                </div>
            </div>

            <!-- Timeline -->
            <div class="chart-card">
                <h3><i class="fas fa-chart-line"></i> Timeline de Acordos</h3>
                <div class="chart-container">
                    <canvas id="timelineChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Últimos Acordos -->
        <div class="table-card">
            <h3><i class="fas fa-list"></i> Últimos Acordos Firmados</h3>
            <div class="table-container">
                <table class="notion-table" id="ultimosAcordosTable">
                    <thead>
                        <tr>
                            <th>CPF</th>
                            <th>Nome</th>
                            <th>Tipo</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody id="ultimosAcordosBody">
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2rem;">
                                <div class="spinner"></div>
                                Carregando...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        let charts = {};

        async function recarregarDados() {
            const dias = document.getElementById('diasFilter').value;
            await Promise.all([
                carregarDadosConsolidados(dias),
                carregarEstatisticasAcordos(dias),
                carregarTimeline(dias),
            ]);
        }

        async function carregarDadosConsolidados(dias) {
            try {
                const token = localStorage.getItem('api_token');
                const response = await fetch(`/api/dashboard/consolidated?days=${dias}`, {
                    headers: {
                        'Authorization': `Bearer ${token}`
                    }
                });

                if (!response.ok) throw new Error('Erro ao carregar dados');

                const {
                    data
                } = await response.json();
                renderizarKPIs(data);
                renderizarConversao(data.conversao);
            } catch (error) {
                console.error('Erro:', error);
                document.getElementById('kpiContainer').innerHTML =
                    '<div style="grid-column: 1/-1; text-align: center; color: red;">Erro ao carregar dados</div>';
            }
        }

        async function carregarEstatisticasAcordos(dias) {
            try {
                const token = localStorage.getItem('api_token');
                const response = await fetch(`/api/dashboard/agreements?days=${dias}`, {
                    headers: {
                        'Authorization': `Bearer ${token}`
                    }
                });

                if (!response.ok) throw new Error('Erro ao carregar estatísticas');

                const {
                    data
                } = await response.json();
                renderizarGraficos(data);
                renderizarUltimosAcordos(data.ultimos_acordos);
            } catch (error) {
                console.error('Erro:', error);
            }
        }

        async function carregarTimeline(dias) {
            try {
                const token = localStorage.getItem('api_token');
                const response = await fetch(`/api/dashboard/timeline?days=${dias}`, {
                    headers: {
                        'Authorization': `Bearer ${token}`
                    }
                });

                if (!response.ok) throw new Error('Erro ao carregar timeline');

                // Timeline é usado apenas internamente
            } catch (error) {
                console.error('Erro:', error);
            }
        }

        function renderizarKPIs(data) {
            const {
                ligacoes,
                acordos,
                conversao,
                valor_consolidado
            } = data;

            const html = `
            <div class="kpi-card">
                <div class="kpi-icon"><i class="fas fa-phone"></i></div>
                <div class="kpi-label">Ligações Atendidas</div>
                <div class="kpi-value">${ligacoes.total_atendidas}</div>
                <div class="kpi-subtitle">Taxa atendimento: ${ligacoes.taxa_atendimento}%</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon"><i class="fas fa-handshake"></i></div>
                <div class="kpi-label">Acordos Firmados</div>
                <div class="kpi-value">${acordos.total_acordos}</div>
                <div class="kpi-subtitle">Valor total: R$ ${acordos.valor_total.toFixed(2)}</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon"><i class="fas fa-percentage"></i></div>
                <div class="kpi-label">Taxa de Conversão</div>
                <div class="kpi-value">${conversao.taxa_conversao_pct}%</div>
                <div class="kpi-subtitle">${conversao.acordos_confirmados} / ${conversao.ligacoes_atendidas}</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon"><i class="fas fa-money-bill-wave"></i></div>
                <div class="kpi-label">Valor Acordado</div>
                <div class="kpi-value">R$ ${acordos.valor_total.toFixed(2)}</div>
                <div class="kpi-subtitle">Média: R$ ${acordos.valor_medio.toFixed(2)}</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon"><i class="fas fa-clock"></i></div>
                <div class="kpi-label">Duração Média</div>
                <div class="kpi-value">${Math.floor(ligacoes.duracao_media)}s</div>
                <div class="kpi-subtitle">Total: ${Math.floor(ligacoes.duracao_total / 60)}min</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon"><i class="fas fa-tag"></i></div>
                <div class="kpi-label">Desconto Concedido</div>
                <div class="kpi-value">R$ ${valor_consolidado.desconto_concedido.toFixed(2)}</div>
                <div class="kpi-subtitle">Economia para cliente</div>
            </div>
        `;

            document.getElementById('kpiContainer').innerHTML = html;
        }

        function renderizarConversao(conversao) {
            const ctx = document.getElementById('conversaoChart');
            if (!ctx) return;

            const percentual = conversao.taxa_conversao_pct;
            const restante = 100 - percentual;

            if (charts.conversao) charts.conversao.destroy();

            charts.conversao = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Acordos', 'Sem Acordo'],
                    datasets: [{
                        data: [percentual, restante],
                        backgroundColor: ['#c8e6c9', '#e0e0e0'],
                        borderColor: ['#1b5e20', '#9e9e9e'],
                        borderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => {
                                    const value = ctx.parsed || 0;
                                    return `${ctx.label}: ${value.toFixed(1)}%`;
                                }
                            }
                        }
                    }
                }
            });
        }

        function renderizarGraficos(data) {
            renderizarAcordosPorTipo(data.por_tipo);
            renderizarAcordosPorStatus(data.por_status);
            renderizarTimelineAcordos(data.timeline);
        }

        function renderizarAcordosPorTipo(tipos) {
            const ctx = document.getElementById('acordosTipoChart');
            if (!ctx || !tipos.length) return;

            const labels = tipos.map(t => t.tipo);
            const quantities = tipos.map(t => t.quantidade);

            if (charts.acordosTipo) charts.acordosTipo.destroy();

            charts.acordosTipo = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Quantidade de Acordos',
                        data: quantities,
                        backgroundColor: '#bbdefb',
                        borderColor: '#01579b',
                        borderWidth: 1,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: {
                            display: true
                        }
                    }
                }
            });
        }

        function renderizarAcordosPorStatus(status) {
            const ctx = document.getElementById('acordosStatusChart');
            if (!ctx || !status.length) return;

            const labels = status.map(s => s.status);
            const quantities = status.map(s => s.total);

            if (charts.acordosStatus) charts.acordosStatus.destroy();

            charts.acordosStatus = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: quantities,
                        backgroundColor: ['#c8e6c9', '#a5d6a7', '#ffcdd2'],
                        borderColor: ['#1b5e20', '#2e7d32', '#b71c1c'],
                        borderWidth: 2,
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

        function renderizarTimelineAcordos(timeline) {
            const ctx = document.getElementById('timelineChart');
            if (!ctx || !timeline.length) return;

            const labels = timeline.map(t => t.data);
            const quantidades = timeline.map(t => t.quantidade);

            if (charts.timeline) charts.timeline.destroy();

            charts.timeline = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Acordos por Dia',
                        data: quantidades,
                        borderColor: '#01579b',
                        backgroundColor: 'rgba(59, 89, 152, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true
                        }
                    }
                }
            });
        }

        function renderizarUltimosAcordos(acordos) {
            if (!acordos.length) {
                document.getElementById('ultimosAcordosBody').innerHTML =
                    '<tr><td colspan="6" style="text-align: center; padding: 2rem;">Nenhum acordo encontrado</td></tr>';
                return;
            }

            const html = acordos.map(acordo => `
            <tr>
                <td>${acordo.cpf}</td>
                <td>${acordo.nome}</td>
                <td>${acordo.parcelas}${acordo.parcelas === 1 ? ' (À Vista)' : 'x'}</td>
                <td>${acordo.valor}</td>
                <td><span class="status-badge status-${acordo.status}">${acordo.status.replace('_', ' ')}</span></td>
                <td>${acordo.data}</td>
            </tr>
        `).join('');

            document.getElementById('ultimosAcordosBody').innerHTML = html;
        }

        // Carregar dados ao iniciar
        document.addEventListener('DOMContentLoaded', recarregarDados);

        // Auto-refresh a cada 5 minutos
        setInterval(() => {
            const dias = document.getElementById('diasFilter').value;
            carregarDadosConsolidados(dias);
        }, 5 * 60 * 1000);
    </script>
@endsection
