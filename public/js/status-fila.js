// Configurações
let refreshInterval = null;
let lastUpdateTime = new Date();
let charts = {};
let historicoDados = [];

// Inicializar
document.addEventListener('DOMContentLoaded', function() {
    carregarDadosFila();

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

    // Registrar callbacks para atualizar fila
    realtime.on('fila-atualizada', function(stats) {
        renderizarMetricas(stats);
    });

    realtime.on('job-completado', function(job) {
        const msg = `Contato: ${job.contato_nome || job.contato_id}`;
        mostrarAlerta('success', `Job completado com sucesso!`);
        notificacoes.sucesso('Job Completado', msg);
        setTimeout(carregarDadosFila, 500);
    });

    realtime.on('job-falhou', function(job) {
        const msg = `Contato: ${job.contato_nome || job.contato_id} - Tentativa ${job.tentativas}`;
        mostrarAlerta('warning', `Job falhou. Aguardando retry...`);
        if (job.tentativas <= 2) {
            notificacoes.aviso('Job Falhou', msg);
        }
        setTimeout(carregarDadosFila, 500);
    });
});

/**
 * Carrega dados da fila
 */
function carregarDadosFila() {
    lastUpdateTime = new Date();

    Promise.all([
        fetch(`${API_BASE_URL}/queues/stats`).then(r => r.json()),
        fetch(`${API_BASE_URL}/queues`).then(r => r.json())
    ])
    .then(([stats, queues]) => {
        renderizarMetricas(stats);
        renderizarTabs(queues);
        atualizarTimestamp();
    })
    .catch(error => {
        console.error('Erro ao carregar fila:', error);
        mostrarAlerta('erro', 'Erro ao carregar dados da fila. Verifique a conexão com o servidor.');
    });
}

/**
 * Renderiza métricas principais
 */
function renderizarMetricas(stats) {
    if (!stats.data) {
        console.error('Formato de resposta inválido:', stats);
        return;
    }

    const data = stats.data;

    document.getElementById('queueTotal').textContent = data.total || 0;
    document.getElementById('pendingCount').textContent = data.pending || 0;
    document.getElementById('processingCount').textContent = data.processing || 0;
    document.getElementById('completedCount').textContent = data.completed || 0;
    document.getElementById('failedCount').textContent = data.failed || 0;

    // Taxa de processamento (jobs por minuto)
    const processRate = data.process_rate ? Math.round(data.process_rate * 100) / 100 : 0;
    document.getElementById('processRate').textContent = processRate;

    // Alertas
    renderizarAlertas(data);

    // Gráficos
    atualizarGraficos(stats);
}

/**
 * Renderiza alertas baseado em condições
 */
function renderizarAlertas(data) {
    const alertsContainer = document.getElementById('alertsContainer');
    alertsContainer.innerHTML = '';

    const alerts = [];

    // Alerta: Fila com muitos jobs
    if (data.total > 1000) {
        alerts.push({
            type: 'warning',
            icon: 'exclamation-triangle',
            message: `Fila com muitos jobs (${data.total}). Taxa de processamento pode estar baixa.`
        });
    }

    // Alerta: Taxa de erro alta
    if (data.total > 0 && data.failed > 0) {
        const errorRate = ((data.failed / data.total) * 100).toFixed(1);
        if (errorRate > 10) {
            alerts.push({
                type: 'danger',
                icon: 'times-circle',
                message: `Taxa de erro alta (${errorRate}%). Verifique os erros da fila.`
            });
        }
    }

    // Alerta: Fila parada
    if (data.processing === 0 && data.pending > 0) {
        alerts.push({
            type: 'warning',
            icon: 'pause-circle',
            message: 'Fila parada. Nenhum job em processamento.'
        });
    }

    // Alerta: Sucesso
    if (data.completed > 100) {
        alerts.push({
            type: 'success',
            icon: 'check-circle',
            message: `Processamento em andamento. ${data.completed} jobs completados.`
        });
    }

    // Renderizar alertas
    alerts.forEach(alert => {
        alertsContainer.innerHTML += `
            <div class="alert alert-${alert.type}">
                <i class="fas fa-${alert.icon}"></i>
                ${alert.message}
            </div>
        `;
    });
}

/**
 * Renderiza conteúdo das abas
 */
function renderizarTabs(queues) {
    if (!queues.data) {
        console.error('Formato de resposta inválido:', queues);
        return;
    }

    const data = queues.data;

    // Overview
    renderizarOverview(data);

    // Jobs por status
    renderizarJobsPorStatus(data);
}

/**
 * Renderiza overview da fila
 */
function renderizarOverview(data) {
    const container = document.getElementById('overviewContainer');

    const mailings = data.mailings || [];

    if (mailings.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>Nenhuma campanha em processamento</p>
            </div>
        `;
        return;
    }

    container.innerHTML = mailings.map(mailing => {
        const progressPercent = mailing.total > 0 ? ((mailing.completed / mailing.total) * 100).toFixed(1) : 0;

        return `
            <div style="padding: 15px; background: #f9f9f9; border-radius: 4px; border-left: 4px solid #00194A;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <h4 style="margin: 0;">${mailing.name}</h4>
                    <span style="font-size: 12px; color: #999;">${mailing.total} total</span>
                </div>
                <div class="progress-bar-simple">
                    <div class="progress-fill" style="width: ${progressPercent}%"></div>
                </div>
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-top: 10px; font-size: 12px;">
                    <div>
                        <strong style="color: #ff9800;">${mailing.pending || 0}</strong>
                        <div style="color: #999;">Pendentes</div>
                    </div>
                    <div>
                        <strong style="color: #2196f3;">${mailing.processing || 0}</strong>
                        <div style="color: #999;">Processando</div>
                    </div>
                    <div>
                        <strong style="color: #4caf50;">${mailing.completed || 0}</strong>
                        <div style="color: #999;">Completados</div>
                    </div>
                    <div>
                        <strong style="color: #f44336;">${mailing.failed || 0}</strong>
                        <div style="color: #999;">Erros</div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

/**
 * Renderiza jobs por status
 */
function renderizarJobsPorStatus(data) {
    const jobs = data.jobs || [];

    // Separar por status
    const pending = jobs.filter(j => j.status === 'pending').slice(0, 10);
    const processing = jobs.filter(j => j.status === 'processing').slice(0, 10);
    const completed = jobs.filter(j => j.status === 'completed').slice(0, 10);
    const failed = jobs.filter(j => j.status === 'failed').slice(0, 10);

    renderizarJobTab('pending', pending, 'pendingContainer');
    renderizarJobTab('processing', processing, 'processingContainer');
    renderizarJobTab('completed', completed, 'completedContainer');
    renderizarJobTab('failed', failed, 'failedContainer');
}

/**
 * Renderiza jobs em uma aba
 */
function renderizarJobTab(status, jobs, containerId) {
    const container = document.getElementById(containerId);

    if (jobs.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>Nenhum job encontrado</p>
            </div>
        `;
        return;
    }

    container.innerHTML = jobs.map(job => {
        const dataFormatada = job.created_at ? new Date(job.created_at).toLocaleString('pt-BR') : 'N/A';
        const proximaTentativa = job.proxima_tentativa ? new Date(job.proxima_tentativa).toLocaleString('pt-BR') : 'N/A';
        const statusClass = getStatusJobClass(job.status);

        return `
            <div class="job-item ${statusClass}">
                <div class="job-info">
                    <div class="job-title">
                        <i class="fas fa-phone"></i>
                        ${job.contato_nome || `Contato #${job.contato_id}`}
                    </div>
                    <div class="job-detail">
                        <strong>Campanha:</strong> ${job.mailing_nome || 'N/A'}
                    </div>
                    <div class="job-detail">
                        <strong>Telefone:</strong> ${formatarTelefone(job.contato_telefone || '')}
                    </div>
                    <div class="job-detail">
                        <strong>Criado:</strong> ${dataFormatada}
                        ${job.proxima_tentativa ? ` | <strong>Próxima tentativa:</strong> ${proximaTentativa}` : ''}
                    </div>
                    ${job.tentativas > 0 ? `<div class="job-detail" style="color: #ff9800;"><i class="fas fa-redo"></i> Tentativa ${job.tentativas}</div>` : ''}
                </div>
                <span class="job-status status-${statusClass}">${getStatusLabel(job.status)}</span>
                <div class="job-actions">
                    ${job.status === 'failed' ? `
                        <button class="btn-xs btn-xs-primary" onclick="reprocessarJob(${job.id})">
                            <i class="fas fa-redo"></i>
                        </button>
                    ` : ''}
                    <button class="btn-xs btn-xs-danger" onclick="removerJob(${job.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

/**
 * Obtém classe CSS do status
 */
function getStatusJobClass(status) {
    const map = {
        'pending': 'pending',
        'processing': 'processing',
        'completed': 'completed',
        'failed': 'failed'
    };
    return map[status] || 'pending';
}

/**
 * Obtém label do status
 */
function getStatusLabel(status) {
    const map = {
        'pending': 'Pendente',
        'processing': 'Processando',
        'completed': 'Completado',
        'failed': 'Falha'
    };
    return map[status] || status;
}

/**
 * Formata telefone
 */
function formatarTelefone(telefone) {
    const numeros = telefone.replace(/[^0-9]/g, '');
    if (numeros.length === 11) {
        return `(${numeros.substring(0, 2)}) ${numeros.substring(2, 7)}-${numeros.substring(7)}`;
    }
    return telefone;
}

/**
 * Muda de aba
 */
function mudarAba(novaAba) {
    // Desativar todas as abas
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
    });

    // Ativar nova aba
    document.getElementById(novaAba).classList.add('active');
    event.target.classList.add('active');
}

/**
 * Pausa fila
 */
function pausarFila() {
    if (!confirm('Deseja pausar a fila de processamento?')) return;

    fetch(`${API_BASE_URL}/queues/pausar`, { method: 'POST' })
        .then(response => response.json())
        .then(data => {
            mostrarAlerta('success', 'Fila pausada com sucesso!');
            setTimeout(carregarDadosFila, 1000);
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarAlerta('erro', 'Erro ao pausar fila');
        });
}

/**
 * Retoma fila
 */
function retomarFila() {
    if (!confirm('Deseja retomar a fila de processamento?')) return;

    fetch(`${API_BASE_URL}/queues/retomar`, { method: 'POST' })
        .then(response => response.json())
        .then(data => {
            mostrarAlerta('success', 'Fila retomada com sucesso!');
            setTimeout(carregarDadosFila, 1000);
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarAlerta('erro', 'Erro ao retomar fila');
        });
}

/**
 * Limpa jobs antigos
 */
function limparJobs() {
    if (!confirm('Deseja remover jobs com mais de 30 dias? Esta ação não pode ser desfeita.')) return;

    fetch(`${API_BASE_URL}/queues/cleanup`, { method: 'POST' })
        .then(response => response.json())
        .then(data => {
            mostrarAlerta('success', `${data.removed || 0} jobs removidos com sucesso!`);
            setTimeout(carregarDadosFila, 1000);
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarAlerta('erro', 'Erro ao limpar jobs');
        });
}

/**
 * Reprocessa jobs com falha
 */
function reprocessarFalhas() {
    if (!confirm('Deseja reprocessar todos os jobs com falha?')) return;

    fetch(`${API_BASE_URL}/queues/reprocessar-falhas`, { method: 'POST' })
        .then(response => response.json())
        .then(data => {
            mostrarAlerta('success', `${data.reprocessed || 0} jobs agendados para reprocessamento!`);
            setTimeout(carregarDadosFila, 1000);
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarAlerta('erro', 'Erro ao reprocessar falhas');
        });
}

/**
 * Reprocessa job específico
 */
function reprocessarJob(jobId) {
    if (!confirm('Deseja reprocessar este job?')) return;

    fetch(`${API_BASE_URL}/queues/${jobId}/reprocessar`, { method: 'POST' })
        .then(response => response.json())
        .then(data => {
            mostrarAlerta('success', 'Job agendado para reprocessamento!');
            setTimeout(carregarDadosFila, 1000);
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarAlerta('erro', 'Erro ao reprocessar job');
        });
}

/**
 * Remove job específico
 */
function removerJob(jobId) {
    if (!confirm('Deseja remover este job da fila?')) return;

    fetch(`${API_BASE_URL}/queues/${jobId}`, { method: 'DELETE' })
        .then(response => response.json())
        .then(data => {
            mostrarAlerta('success', 'Job removido com sucesso!');
            setTimeout(carregarDadosFila, 1000);
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarAlerta('erro', 'Erro ao remover job');
        });
}

/**
 * Mostra alerta
 */
function mostrarAlerta(tipo, mensagem) {
    const alertsContainer = document.getElementById('alertsContainer');
    const iconMap = {
        'success': 'check-circle',
        'erro': 'times-circle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };

    const alert = document.createElement('div');
    alert.className = `alert alert-${tipo === 'erro' ? 'danger' : tipo}`;
    alert.innerHTML = `<i class="fas fa-${iconMap[tipo]}"></i> ${mensagem}`;

    alertsContainer.insertBefore(alert, alertsContainer.firstChild);

    // Auto-remover após 5 segundos
    setTimeout(() => {
        alert.style.opacity = '0';
        alert.style.transition = 'opacity 0.3s';
        setTimeout(() => alert.remove(), 300);
    }, 5000);
}

/**
 * Atualiza timestamp
 */
function atualizarTimestamp() {
    const now = new Date();
    const hora = now.toLocaleTimeString('pt-BR');
    document.getElementById('lastUpdate').textContent = `Atualizado em ${hora}`;
}

/**
 * Inicia auto-refresh
 */
function iniciarAutoRefresh() {
    const interval = parseInt(document.getElementById('refreshInterval').value) || 3;
    refreshInterval = setInterval(() => {
        carregarDadosFila();
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
 * Exporta dados da fila em CSV
 */
function exportarFilaCSV() {
    const stats = {
        data: {
            total: document.getElementById('queueTotal').textContent,
            pending: document.getElementById('pendingCount').textContent,
            processing: document.getElementById('processingCount').textContent,
            completed: document.getElementById('completedCount').textContent,
            failed: document.getElementById('failedCount').textContent,
            process_rate: document.getElementById('processRate').textContent
        }
    };

    let csv = 'STATUS DA FILA DE PROCESSAMENTO\n';
    csv += `Gerado em: ${new Date().toLocaleString('pt-BR')}\n\n`;

    csv += 'MÉTRICAS\n';
    csv += `Total na Fila,${stats.data.total}\n`;
    csv += `Pendentes,${stats.data.pending}\n`;
    csv += `Em Processamento,${stats.data.processing}\n`;
    csv += `Completados,${stats.data.completed}\n`;
    csv += `Com Falha,${stats.data.failed}\n`;
    csv += `Taxa de Processamento (jobs/min),${stats.data.process_rate}\n\n`;

    csv += 'HISTÓRICO\n';
    csv += 'Tempo,Completados,Falhados,Pendentes\n';
    historicoDados.forEach(d => {
        csv += `${d.tempo},${d.completed},${d.failed},${d.pending}\n`;
    });

    downloadCSV(csv, `fila_${new Date().getTime()}.csv`);
    mostrarAlerta('success', 'Dados da fila exportados com sucesso!');
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
 * Atualiza os gráficos
 */
function atualizarGraficos(stats) {
    if (!stats.data) return;

    const data = stats.data;

    // Adicionar ao histórico (últimos 20 pontos)
    historicoDados.push({
        tempo: new Date().toLocaleTimeString('pt-BR'),
        total: data.total,
        pending: data.pending,
        processing: data.processing,
        completed: data.completed,
        failed: data.failed
    });

    if (historicoDados.length > 20) {
        historicoDados.shift();
    }

    // Gráfico 1: Distribuição de Status
    const ctxDistribuicao = document.getElementById('chartStatusDistribuicao');
    if (charts.distribuicao) {
        charts.distribuicao.destroy();
    }
    charts.distribuicao = new Chart(ctxDistribuicao, {
        type: 'doughnut',
        data: {
            labels: ['Pendentes', 'Processando', 'Completados', 'Falhados'],
            datasets: [{
                data: [data.pending || 0, data.processing || 0, data.completed || 0, data.failed || 0],
                backgroundColor: ['#ff9800', '#2196f3', '#4caf50', '#f44336'],
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

    // Gráfico 2: Evolução do Processamento (linha)
    const ctxProcessamento = document.getElementById('chartProcessamento');
    if (charts.processamento) {
        charts.processamento.destroy();
    }
    charts.processamento = new Chart(ctxProcessamento, {
        type: 'line',
        data: {
            labels: historicoDados.map(d => d.tempo),
            datasets: [
                {
                    label: 'Completados',
                    data: historicoDados.map(d => d.completed),
                    borderColor: '#4caf50',
                    backgroundColor: 'rgba(76, 175, 80, 0.1)',
                    tension: 0.4,
                    borderWidth: 2
                },
                {
                    label: 'Falhados',
                    data: historicoDados.map(d => d.failed),
                    borderColor: '#f44336',
                    backgroundColor: 'rgba(244, 67, 54, 0.1)',
                    tension: 0.4,
                    borderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: { beginAtZero: true }
            },
            plugins: {
                legend: { position: 'top' }
            }
        }
    });

    // Gráfico 3: Mailings (bar)
    const mailings = data.mailings || [];
    if (mailings.length > 0) {
        const ctxMailings = document.getElementById('chartMailings');
        if (charts.mailings) {
            charts.mailings.destroy();
        }
        charts.mailings = new Chart(ctxMailings, {
            type: 'bar',
            data: {
                labels: mailings.slice(0, 5).map(m => m.name.substring(0, 12)),
                datasets: [
                    {
                        label: 'Pendentes',
                        data: mailings.slice(0, 5).map(m => m.pending || 0),
                        backgroundColor: '#ff9800'
                    },
                    {
                        label: 'Completados',
                        data: mailings.slice(0, 5).map(m => m.completed || 0),
                        backgroundColor: '#4caf50'
                    }
                ]
            },
            options: {
                indexAxis: 'x',
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: { stacked: false, beginAtZero: true }
                }
            }
        });
    }
}
