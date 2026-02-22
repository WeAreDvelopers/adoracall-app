let campanhaAtualId = null;
let refreshInterval = null;
let chartStatus = null;

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando status-ligacoes.js');
    console.log('📍 API_BASE_URL:', API_BASE_URL);

    carregarCampanhas();

    document.getElementById('campanhaSelect').addEventListener('change', function() {
        campanhaAtualId = this.value;
        console.log('🔄 Campanha selecionada:', campanhaAtualId);

        if (campanhaAtualId) {
            iniciarMonitoramento();
        } else {
            pararMonitoramento();
            limparDados();
        }
    });

    // Tabs de filtro
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.tab-button').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            carregarLigacoes(this.dataset.status);
        });
    });
});

async function carregarCampanhas() {
    try {
        console.log('📥 Carregando campanhas...');
        const response = await fetch(`${API_BASE_URL}/filas_campanha`);
        const data = await response.json();

        const select = document.getElementById('campanhaSelect');
        select.innerHTML = '<option value="">Selecione uma campanha...</option>';

        if (data.data && data.data.length > 0) {
            data.data.forEach(campanha => {
                const option = document.createElement('option');
                option.value = campanha.id;
                option.textContent = `${campanha.nome} (${campanha.status})`;
                select.appendChild(option);
            });
            console.log('✅ Campanhas carregadas:', data.data.length);
        } else {
            console.log('⚠️ Nenhuma campanha encontrada');
        }
    } catch (error) {
        console.error('❌ Erro ao carregar campanhas:', error);
    }
}

function iniciarMonitoramento() {
    console.log('🔄 Iniciando monitoramento da campanha', campanhaAtualId);
    pararMonitoramento();
    atualizarDados();
    refreshInterval = setInterval(atualizarDados, 5000); // A cada 5s
}

function pararMonitoramento() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
        console.log('⏹️ Monitoramento parado');
    }
}

function limparDados() {
    console.log('🧹 Limpando dados');
    document.getElementById('metricTotal').textContent = '-';
    document.getElementById('metricEmLigacao').textContent = '-';
    document.getElementById('metricFinalizadas').textContent = '-';
    document.getElementById('metricFalhas').textContent = '-';
    document.getElementById('metricTaxaSucesso').textContent = '-%';
    document.getElementById('progressPercent').textContent = '0%';
    document.getElementById('progressFill').style.width = '0%';
    document.getElementById('ligacoesLista').innerHTML = '';

    if (chartStatus) {
        chartStatus.destroy();
        chartStatus = null;
    }
}

async function atualizarDados() {
    if (!campanhaAtualId) return;

    try {
        console.log('📊 Atualizando dados da campanha', campanhaAtualId);
        const response = await fetch(`${API_BASE_URL}/filas_campanha/${campanhaAtualId}/ligacoes/stats`);
        const data = await response.json();

        console.log('📈 Dados recebidos:', data);
        atualizarMetricas(data);
        atualizarGraficos(data);
        carregarLigacoes('all');
    } catch (error) {
        console.error('❌ Erro ao atualizar dados:', error);
    }
}

function atualizarMetricas(data) {
    console.log('📊 Atualizando métricas');

    document.getElementById('metricTotal').textContent = data.progresso.total || 0;
    document.getElementById('metricEmLigacao').textContent = data.contatos.em_ligacao || 0;
    document.getElementById('metricFinalizadas').textContent = data.contatos.finalizado || 0;
    document.getElementById('metricFalhas').textContent = data.contatos.falhou || 0;

    const taxaSucesso = (data.progresso.taxa_sucesso || 0).toFixed(1);
    document.getElementById('metricTaxaSucesso').textContent = taxaSucesso + '%';

    const percentual = data.progresso.percentual || 0;
    document.getElementById('progressPercent').textContent = percentual.toFixed(1) + '%';
    document.getElementById('progressFill').style.width = percentual + '%';
}

function atualizarGraficos(data) {
    console.log('📈 Atualizando gráficos');

    // Gráfico de Status (Donut)
    const ctxStatus = document.getElementById('chartStatus');
    if (chartStatus) {
        chartStatus.destroy();
    }

    chartStatus = new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: ['Pendentes', 'Em Ligação', 'Finalizadas', 'Falhas'],
            datasets: [{
                data: [
                    data.queue.pending || 0,
                    data.queue.processing || 0,
                    data.queue.completed || 0,
                    data.queue.failed || 0
                ],
                backgroundColor: ['#ff9800', '#2196f3', '#4caf50', '#f44336'],
                borderColor: ['#fff'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
}

async function carregarLigacoes(status) {
    if (!campanhaAtualId) return;

    try {
        console.log('📱 Carregando ligações com status:', status);
        let url = `${API_BASE_URL}/filas_campanha/${campanhaAtualId}/ligacoes`;
        if (status !== 'all') {
            url += `?status=${status}`;
        }

        const response = await fetch(url);
        const data = await response.json();

        console.log('📊 Ligações carregadas:', data.data?.length || 0);

        const lista = document.getElementById('ligacoesLista');
        lista.innerHTML = '';

        if (!data.data || data.data.length === 0) {
            lista.innerHTML = '<div class="empty-state"><i class="fas fa-phone-slash"></i><p>Nenhuma ligação encontrada.</p></div>';
            return;
        }

        data.data.forEach(ligacao => {
            const item = document.createElement('div');
            const statusClass = ligacao.status === 'finalizado' ? 'finalizado' : ligacao.status;
            item.className = `job-item ${statusClass}`;

            const contato = ligacao.contato || {};
            const duracao = ligacao.duracao ? `${ligacao.duracao}s` : '0s';
            const statusDisplay = ligacao.status === 'finalizado' ? 'FINALIZADA' :
                                 ligacao.status === 'falhou' ? 'FALHOU' :
                                 'EM LIGAÇÃO';

            item.innerHTML = `
                <div class="job-info">
                    <div class="job-title">📞 ${contato.nome || 'Desconhecido'} ${contato.sobrenome || ''}</div>
                    <div class="job-detail">☎️ ${contato.telefone || '-'}</div>
                    <div class="job-detail">⏱️ ${duracao}</div>
                </div>
                <span class="job-status status-${statusClass}">${statusDisplay}</span>
            `;
            lista.appendChild(item);
        });
    } catch (error) {
        console.error('❌ Erro ao carregar ligações:', error);
        const lista = document.getElementById('ligacoesLista');
        lista.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>Erro ao carregar ligações.</p></div>';
    }
}
