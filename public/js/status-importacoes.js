// Configurações
let allMailings = [];
let filteredMailings = [];
let refreshInterval = null;
let charts = {};

// Status mapping
const STATUS_MAP = {
    'importado': { label: 'Importado', color: '#1976d2' },
    'enviado_a_discagem': { label: 'Enviado a Discagem', color: '#7b1fa2' },
    'em_ligacao': { label: 'Em Ligação', color: '#f57c00' },
    'finalizado': { label: 'Finalizado', color: '#388e3c' },
    'retentar': { label: 'Retentar', color: '#d32f2f' }
};

// Inicializar
document.addEventListener('DOMContentLoaded', function() {
    carregarMailings();

    document.getElementById('statusFilter').addEventListener('change', aplicarFiltros);
    document.getElementById('searchFilter').addEventListener('keyup', debounce(aplicarFiltros, 500));

    document.getElementById('autoRefresh').addEventListener('change', function() {
        if (this.checked) {
            iniciarAutoRefresh();
        } else {
            pararAutoRefresh();
        }
    });

    document.getElementById('refreshInterval').addEventListener('change', function() {
        pararAutoRefresh();
        if (document.getElementById('autoRefresh').checked) {
            iniciarAutoRefresh();
        }
    });

    iniciarAutoRefresh();

    // Conectar ao sistema de tempo real
    realtime.conectar();

    // Registrar callbacks para atualizar mailings
    realtime.on('mailings-atualizadas', function(mailings) {
        if (Array.isArray(mailings)) {
            allMailings = mailings;
            aplicarFiltros();
        }
    });

    realtime.on('mailing-atualizado', function(mailing) {
        const index = allMailings.findIndex(m => m.id === mailing.id);
        if (index >= 0) {
            allMailings[index] = mailing;
            aplicarFiltros();
        }
    });
});

/**
 * Carrega lista de mailings
 */
function carregarMailings() {
    fetch(`${API_BASE_URL}/filas_campanha`)
        .then(response => response.json())
        .then(data => {
            if (data.data && Array.isArray(data.data)) {
                allMailings = data.data;
                filteredMailings = [...allMailings];
                renderMailings();
            }
        })
        .catch(error => {
            console.error('Erro ao carregar mailings:', error);
            document.getElementById('mailingsList').innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>Erro ao carregar campanhas. Tente novamente.</p>
                </div>
            `;
        });
}

/**
 * Renderiza lista de mailings
 */
function renderMailings() {
    const container = document.getElementById('mailingsList');
    const titleElement = document.getElementById('mailingCount');

    if (filteredMailings.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>Nenhuma campanha encontrada</p>
            </div>
        `;
        titleElement.textContent = 'Campanhas Importadas (0)';
        document.getElementById('chartsCard').style.display = 'none';
        return;
    }

    titleElement.textContent = `Campanhas Importadas (${filteredMailings.length})`;

    container.innerHTML = filteredMailings.map(mailing => {
        const stats = calcularEstatisticas(mailing);
        const statusClass = getStatusClass(mailing.status);
        const statusLabel = getStatusLabel(mailing.status);
        const dataFormatada = new Date(mailing.created_at).toLocaleString('pt-BR');

        return `
            <div class="mailing-card ${statusClass}">
                <div class="mailing-header">
                    <div class="mailing-title">
                        <h3>${mailing.nome}</h3>
                        <small>Criada em: ${dataFormatada}</small>
                    </div>
                    <span class="mailing-status-badge status-${statusClass}">
                        <i class="fas fa-${getStatusIcon(mailing.status)}"></i>
                        ${statusLabel}
                    </span>
                </div>

                <div class="mailing-stats">
                    <div class="stat-item">
                        <div class="stat-number">${stats.total}</div>
                        <div class="stat-label">Total</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number" style="color: #1976d2;">${stats.importado}</div>
                        <div class="stat-label">Importados</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number" style="color: #7b1fa2;">${stats.enviado}</div>
                        <div class="stat-label">Enviados</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number" style="color: #f57c00;">${stats.ligacao}</div>
                        <div class="stat-label">Em Ligação</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number" style="color: #388e3c;">${stats.finalizado}</div>
                        <div class="stat-label">Finalizados</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number" style="color: #d32f2f;">${stats.erro}</div>
                        <div class="stat-label">Erros</div>
                    </div>
                </div>

                <div class="progress-container">
                    <div class="progress-label">
                        <span>Distribuição de Status</span>
                        <span style="color: #999;">Taxa de Sucesso: ${stats.taxaSucesso}%</span>
                    </div>
                    <div class="progress-bar">
                        ${renderProgressBar(stats)}
                    </div>
                </div>

                <div class="mailing-actions">
                    <button class="btn-small btn-primary-sm" onclick="abrirDetalhes(${mailing.id})">
                        <i class="fas fa-eye"></i> Ver Detalhes
                    </button>
                    ${mailing.status === 'ativo' ? `
                        <button class="btn-small btn-secondary-sm" onclick="pausarMailing(${mailing.id})">
                            <i class="fas fa-pause"></i> Pausar
                        </button>
                    ` : mailing.status === 'pausado' ? `
                        <button class="btn-small btn-primary-sm" onclick="retomarMailing(${mailing.id})">
                            <i class="fas fa-play"></i> Retomar
                        </button>
                    ` : ''}
                    <button class="btn-small btn-secondary-sm" onclick="exportarRelatorio(${mailing.id})">
                        <i class="fas fa-download"></i> Exportar
                    </button>
                </div>
            </div>
        `;
    }).join('');

    // Atualizar gráficos
    atualizarGraficos();
}

/**
 * Calcula estatísticas do mailing
 */
function calcularEstatisticas(mailing) {
    const stats = {
        total: mailing.total_contatos || 0,
        importado: 0,
        enviado: 0,
        ligacao: 0,
        finalizado: 0,
        erro: 0
    };

    // Se houver dados detalhados, usar
    if (mailing.contatos_por_status) {
        stats.importado = mailing.contatos_por_status.importado || 0;
        stats.enviado = mailing.contatos_por_status.enviado_a_discagem || 0;
        stats.ligacao = mailing.contatos_por_status.em_ligacao || 0;
        stats.finalizado = mailing.contatos_por_status.finalizado || 0;
        stats.erro = mailing.contatos_por_status.retentar || 0;
    } else {
        // Aproximação baseada em campo genérico
        stats.importado = mailing.total_contatos || 0;
    }

    const sucesso = stats.importado + stats.enviado + stats.ligacao + stats.finalizado;
    stats.taxaSucesso = stats.total > 0 ? Math.round((sucesso / stats.total) * 100) : 0;

    return stats;
}

/**
 * Renderiza barra de progresso com cores
 */
function renderProgressBar(stats) {
    const total = stats.total || 1;
    let html = '';

    const segments = [
        { count: stats.importado, class: 'progress-importado', label: 'Importado' },
        { count: stats.enviado, class: 'progress-enviado', label: 'Enviado' },
        { count: stats.ligacao, class: 'progress-ligacao', label: 'Ligação' },
        { count: stats.finalizado, class: 'progress-finalizado', label: 'Finalizado' },
        { count: stats.erro, class: 'progress-erro', label: 'Erro' }
    ];

    segments.forEach(seg => {
        if (seg.count > 0) {
            const percentage = ((seg.count / total) * 100).toFixed(1);
            html += `<div class="progress-segment ${seg.class}" style="flex: ${percentage}%; min-width: 30px;" title="${seg.label}: ${seg.count}">${percentage > 10 ? percentage + '%' : ''}</div>`;
        }
    });

    return html;
}

/**
 * Obtém classe de status
 */
function getStatusClass(status) {
    if (status === 'ativo') return 'ativo';
    if (status === 'pausado') return 'pausado';
    if (status === 'concluido') return 'concluido';
    return 'ativo';
}

/**
 * Obtém label de status
 */
function getStatusLabel(status) {
    if (status === 'ativo') return 'Ativo';
    if (status === 'pausado') return 'Pausado';
    if (status === 'concluido') return 'Concluído';
    return status;
}

/**
 * Obtém ícone de status
 */
function getStatusIcon(status) {
    if (status === 'ativo') return 'play-circle';
    if (status === 'pausado') return 'pause-circle';
    if (status === 'concluido') return 'check-circle';
    return 'info-circle';
}

/**
 * Abre modal com detalhes do mailing
 */
function abrirDetalhes(mailingId) {
    const mailing = allMailings.find(m => m.id === mailingId);
    if (!mailing) return;

    const stats = calcularEstatisticas(mailing);
    const dataFormatada = new Date(mailing.created_at).toLocaleString('pt-BR');

    const body = document.getElementById('modalBody');
    body.innerHTML = `
        <div style="display: grid; gap: 15px;">
            <div style="padding: 15px; background: #f9f9f9; border-radius: 4px;">
                <h4 style="margin-top: 0;">${mailing.nome}</h4>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 10px;">
                    <div>
                        <small style="color: #999; font-weight: 600;">Criada em</small>
                        <p style="margin: 5px 0 0 0;">${dataFormatada}</p>
                    </div>
                    <div>
                        <small style="color: #999; font-weight: 600;">Status</small>
                        <p style="margin: 5px 0 0 0;">
                            <span class="mailing-status-badge status-${getStatusClass(mailing.status)}">
                                ${getStatusLabel(mailing.status)}
                            </span>
                        </p>
                    </div>
                    <div>
                        <small style="color: #999; font-weight: 600;">Total de Contatos</small>
                        <p style="margin: 5px 0 0 0; font-size: 18px; font-weight: bold;">${stats.total}</p>
                    </div>
                    <div>
                        <small style="color: #999; font-weight: 600;">Taxa de Sucesso</small>
                        <p style="margin: 5px 0 0 0; font-size: 18px; font-weight: bold; color: ${stats.taxaSucesso >= 80 ? '#4caf50' : stats.taxaSucesso >= 50 ? '#f57c00' : '#f44336'};">
                            ${stats.taxaSucesso}%
                        </p>
                    </div>
                </div>
            </div>

            <div>
                <h4 style="margin-bottom: 10px;">Distribuição de Status</h4>
                <div style="display: grid; gap: 10px;">
                    ${renderDetailLine('Importado', stats.importado, '#1976d2')}
                    ${renderDetailLine('Enviado a Discagem', stats.enviado, '#7b1fa2')}
                    ${renderDetailLine('Em Ligação', stats.ligacao, '#f57c00')}
                    ${renderDetailLine('Finalizado', stats.finalizado, '#388e3c')}
                    ${renderDetailLine('Com Erro', stats.erro, '#d32f2f')}
                </div>
            </div>

            <button class="btn btn-primary" onclick="irParaContatosMailing(${mailingId})" style="width: 100%;">
                <i class="fas fa-list"></i> Ver Todos os Contatos
            </button>
        </div>
    `;

    document.getElementById('mailingModal').classList.add('show');
}

/**
 * Renderiza linha de detalhe
 */
function renderDetailLine(label, value, color) {
    return `
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: #f9f9f9; border-radius: 4px; border-left: 4px solid ${color};">
            <span style="font-size: 14px;">${label}</span>
            <strong style="font-size: 16px; color: ${color};">${value}</strong>
        </div>
    `;
}

/**
 * Fecha modal
 */
function fecharModal() {
    document.getElementById('mailingModal').classList.remove('show');
}

/**
 * Aplica filtros
 */
function aplicarFiltros() {
    const status = document.getElementById('statusFilter').value;
    const busca = document.getElementById('searchFilter').value.toLowerCase();

    filteredMailings = allMailings.filter(mailing => {
        const matchStatus = !status || mailing.status === status;
        const matchBusca = !busca || mailing.nome.toLowerCase().includes(busca);
        return matchStatus && matchBusca;
    });

    renderMailings();
}

/**
 * Limpa filtros
 */
function limparFiltros() {
    document.getElementById('statusFilter').value = '';
    document.getElementById('searchFilter').value = '';
    filteredMailings = [...allMailings];
    renderMailings();
}

/**
 * Pausa mailing
 */
function pausarMailing(mailingId) {
    if (!confirm('Deseja pausar esta campanha?')) return;

    fetch(`${API_BASE_URL}/filas_campanha/${mailingId}/pausar`, { method: 'POST' })
        .then(response => response.json())
        .then(data => {
            alert('Campanha pausada com sucesso!');
            carregarMailings();
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao pausar campanha');
        });
}

/**
 * Retoma mailing
 */
function retomarMailing(mailingId) {
    if (!confirm('Deseja retomar esta campanha?')) return;

    fetch(`${API_BASE_URL}/filas_campanha/${mailingId}/ativar`, { method: 'POST' })
        .then(response => response.json())
        .then(data => {
            alert('Campanha retomada com sucesso!');
            carregarMailings();
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao retomar campanha');
        });
}

/**
 * Exporta relatório em CSV
 */
function exportarRelatorio(mailingId) {
    const mailing = allMailings.find(m => m.id === mailingId);
    if (!mailing) return;

    // Buscar dados completos do mailing
    fetch(`${API_BASE_URL}/filas_campanha/${mailingId}/import-report`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert('Erro ao gerar relatório');
                return;
            }

            // Gerar CSV
            const csv = gerarCSVRelatorio(data, mailing.nome);

            // Download
            downloadCSV(csv, `relatorio_${mailing.nome}_${new Date().getTime()}.csv`);
            mostrarAlerta('success', 'Relatório exportado com sucesso!');
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarAlerta('erro', 'Erro ao exportar relatório');
        });
}

/**
 * Gera CSV do relatório
 */
function gerarCSVRelatorio(data, nomeMailing) {
    let csv = 'RELATÓRIO DE IMPORTAÇÃO\n';
    csv += `Campanha: ${nomeMailing}\n`;
    csv += `Gerado em: ${new Date().toLocaleString('pt-BR')}\n\n`;

    // Estatísticas
    csv += 'ESTATÍSTICAS\n';
    const stats = data.estatisticas;
    csv += `Total de Linhas,${stats.total_linhas}\n`;
    csv += `Sucesso,${stats.sucesso}\n`;
    csv += `Erros,${stats.erros}\n`;
    csv += `Avisos,${stats.avisos}\n`;
    csv += `Ignoradas,${stats.ignoradas}\n`;
    csv += `Duplicadas,${stats.duplicadas}\n`;
    csv += `Taxa de Sucesso,${((stats.sucesso/stats.total_linhas)*100).toFixed(2)}%\n\n`;

    // Performance
    csv += 'PERFORMANCE\n';
    const perf = data.performance;
    csv += `Tempo Total (s),${perf.total_tempo_segundos}\n`;
    csv += `Linhas/Segundo,${perf.linhas_por_segundo}\n`;
    csv += `Tempo Médio (ms),${perf.tempo_medio_por_linha_ms}\n\n`;

    // Top Erros
    if (data.top_erros && data.top_erros.length > 0) {
        csv += 'PRINCIPAIS ERROS\n';
        csv += 'Erro,Ocorrências\n';
        data.top_erros.forEach(erro => {
            csv += `"${erro.mensagem}",${erro.ocorrencias}\n`;
        });
        csv += '\n';
    }

    // Distribuição por DDD
    if (data.resumo_ddd) {
        csv += 'DISTRIBUIÇÃO POR DDD\n';
        csv += 'DDD,Contatos\n';
        Object.entries(data.resumo_ddd).forEach(([ddd, count]) => {
            csv += `${ddd},${count}\n`;
        });
    }

    return csv;
}

/**
 * Faz download de CSV
 */
function downloadCSV(csv, filename) {
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);

    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

/**
 * Vai para contatos do mailing
 */
function irParaContatosMailing(mailingId) {
    // Redireciona para a página de importação com o mailing selecionado
    window.location.href = `importacao.html?mailing_id=${mailingId}`;
}

/**
 * Inicia auto-refresh
 */
function iniciarAutoRefresh() {
    const interval = parseInt(document.getElementById('refreshInterval').value) || 5;
    refreshInterval = setInterval(() => {
        carregarMailings();
    }, interval * 1000);
}

/**
 * Para auto-refresh
 */
function pararAutoRefresh() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }
}

/**
 * Debounce function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Atualiza os gráficos
 */
function atualizarGraficos() {
    if (filteredMailings.length === 0) {
        document.getElementById('chartsCard').style.display = 'none';
        return;
    }

    document.getElementById('chartsCard').style.display = 'block';

    // Dados agregados
    let totalContatos = 0;
    let totalImportado = 0;
    let totalEnviado = 0;
    let totalLigacao = 0;
    let totalFinalizado = 0;
    let totalErro = 0;

    const mailingsComTaxa = filteredMailings.map(mailing => {
        const stats = calcularEstatisticas(mailing);
        totalContatos += stats.total;
        totalImportado += stats.importado;
        totalEnviado += stats.enviado;
        totalLigacao += stats.ligacao;
        totalFinalizado += stats.finalizado;
        totalErro += stats.erro;

        return {
            nome: mailing.nome,
            taxa: stats.taxaSucesso,
            progresso: stats.total > 0 ? ((stats.importado + stats.enviado + stats.ligacao + stats.finalizado) / stats.total) * 100 : 0
        };
    });

    // Gráfico 1: Distribuição de Status
    const ctxDistribuicao = document.getElementById('chartDistribuicao');
    if (charts.distribuicao) {
        charts.distribuicao.destroy();
    }
    charts.distribuicao = new Chart(ctxDistribuicao, {
        type: 'doughnut',
        data: {
            labels: ['Importado', 'Enviado', 'Em Ligação', 'Finalizado', 'Com Erro'],
            datasets: [{
                data: [totalImportado, totalEnviado, totalLigacao, totalFinalizado, totalErro],
                backgroundColor: ['#1976d2', '#7b1fa2', '#f57c00', '#388e3c', '#d32f2f'],
                borderColor: 'white',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

    // Gráfico 2: Taxa de Sucesso
    const ctxTaxa = document.getElementById('chartTaxaSucesso');
    if (charts.taxa) {
        charts.taxa.destroy();
    }
    charts.taxa = new Chart(ctxTaxa, {
        type: 'bar',
        data: {
            labels: mailingsComTaxa.map(m => m.nome.substring(0, 15)),
            datasets: [{
                label: 'Taxa de Sucesso (%)',
                data: mailingsComTaxa.map(m => m.taxa),
                backgroundColor: mailingsComTaxa.map(m =>
                    m.taxa >= 80 ? '#4caf50' : m.taxa >= 50 ? '#ff9800' : '#f44336'
                ),
                borderRadius: 4,
                borderSkipped: false
            }]
        },
        options: {
            indexAxis: 'x',
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: { beginAtZero: true, max: 100 }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });

    // Gráfico 3: Progresso de Processamento
    const ctxProgresso = document.getElementById('chartProgresso');
    if (charts.progresso) {
        charts.progresso.destroy();
    }
    charts.progresso = new Chart(ctxProgresso, {
        type: 'bar',
        data: {
            labels: mailingsComTaxa.map(m => m.nome.substring(0, 15)),
            datasets: [{
                label: 'Progresso (%)',
                data: mailingsComTaxa.map(m => m.progresso),
                backgroundColor: '#ffae00',
                borderRadius: 4,
                borderSkipped: false
            }]
        },
        options: {
            indexAxis: 'x',
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: { beginAtZero: true, max: 100 }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
}

// Fechar modal ao clicar fora
document.addEventListener('click', function(e) {
    const modal = document.getElementById('mailingModal');
    if (e.target === modal) {
        fecharModal();
    }
});
