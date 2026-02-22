@extends('layouts.app')

@section('title', 'Visualizar Campanha - URA Dvelopers')

@section('content')
<style>
    .header-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .info-box {
        background: var(--notion-bg-secondary);
        border-left: 4px solid var(--dvelopers-gold);
        padding: 1rem;
        border-radius: 4px;
    }

    .info-label {
        font-size: 0.8rem;
        color: var(--notion-text-secondary);
        text-transform: uppercase;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .info-value {
        font-size: 1.3rem;
        font-weight: 600;
        color: var(--notion-text);
    }

    .info-value.status-ativo {
        color: #4caf50;
    }

    .info-value.status-pausado {
        color: #ff9800;
    }

    .info-value.status-concluido {
        color: #2196f3;
    }

    .info-value.status-cancelado {
        color: #f44336;
    }

    .progress-bar {
        width: 100%;
        height: 8px;
        background: var(--notion-bg);
        border-radius: 4px;
        overflow: hidden;
        margin-top: 0.5rem;
    }

    .progress-fill {
        height: 100%;
        background: var(--dvelopers-gold);
        transition: width 0.3s ease;
    }

    .contatos-section {
        margin-top: 2rem;
    }

    .contatos-filters {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
        align-items: flex-end;
    }

    .contatos-table-wrapper {
        overflow-x: auto;
        margin-bottom: 2rem;
    }

    .contatos-table {
        width: 100%;
        border-collapse: collapse;
        background: var(--notion-bg);
        border: 1px solid var(--notion-border);
        border-radius: 4px;
        overflow: hidden;
    }

    .contatos-table thead {
        background: var(--notion-bg-secondary);
        border-bottom: 2px solid var(--notion-border);
    }

    .contatos-table th {
        padding: 1rem;
        text-align: left;
        font-weight: 600;
        color: var(--notion-text);
        font-size: 0.9rem;
    }

    .contatos-table td {
        padding: 0.8rem 1rem;
        border-bottom: 1px solid var(--notion-border);
        color: var(--notion-text);
    }

    .contatos-table tbody tr:hover {
        background: var(--notion-bg-secondary);
    }

    .status-badge {
        display: inline-block;
        padding: 0.3rem 0.8rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .status-badge.pendente {
        background: rgba(255, 193, 7, 0.2);
        color: #ffc107;
    }

    .status-badge.processando {
        background: rgba(33, 150, 243, 0.2);
        color: #2196f3;
    }

    .status-badge.completado {
        background: rgba(76, 175, 80, 0.2);
        color: #4caf50;
    }

    .status-badge.falha {
        background: rgba(244, 67, 54, 0.2);
        color: #f44336;
    }

    .empty-state {
        text-align: center;
        padding: 2rem;
        color: var(--notion-text-secondary);
    }

    .empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.3;
    }

    .action-buttons {
        display: flex;
        gap: 0.5rem;
    }

    .action-buttons button {
        padding: 0.4rem 0.8rem;
        font-size: 0.85rem;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }

    .stat-item {
        background: var(--notion-bg);
        border: 1px solid var(--notion-border);
        padding: 1rem;
        border-radius: 4px;
        text-align: center;
    }

    .stat-number {
        font-size: 1.8rem;
        font-weight: 600;
        color: var(--dvelopers-gold);
    }

    .stat-label {
        font-size: 0.85rem;
        color: var(--notion-text-secondary);
        margin-top: 0.5rem;
    }
</style>

<div class="notion-card">
    <div class="notion-card-header">
        <h2><i class="fas fa-eye"></i> Visualizar Campanha</h2>
        <p id="campanhaDesc">Carregando informações da campanha...</p>
    </div>

    <!-- Informações da Campanha -->
    <div id="campanhaInfo"></div>

    <!-- Seção de Contatos -->
    <div class="contatos-section">
        <h3 style="font-size: var(--notion-font-size-lg); color: var(--notion-text); margin-bottom: 1.5rem;">
            <i class="fas fa-users" style="color: var(--dvelopers-gold);"></i> Contatos da Campanha
        </h3>

        <!-- Filtros -->
        <div class="contatos-filters">
            <div class="notion-form-group" style="margin-bottom: 0;">
                <label class="notion-label">Status</label>
                <select class="notion-select" id="filtroStatus" onchange="aplicarFiltros()">
                    <option value="">Todos</option>
                    <option value="pendente">Pendente</option>
                    <option value="processando">Processando</option>
                    <option value="completado">Completado</option>
                    <option value="falha">Falha</option>
                </select>
            </div>
            <div class="notion-form-group" style="margin-bottom: 0;">
                <label class="notion-label">Buscar</label>
                <input
                    type="text"
                    class="notion-input"
                    id="filtroNome"
                    placeholder="Nome ou telefone..."
                    onchange="aplicarFiltros()"
                >
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <button class="notion-btn notion-btn-primary" onclick="aplicarFiltros()" style="margin-bottom: 0;">
                    <i class="fas fa-search"></i> Filtrar
                </button>
                <button class="notion-btn notion-btn-secondary" onclick="limparFiltros()" style="margin-bottom: 0;">
                    <i class="fas fa-undo"></i> Limpar
                </button>
            </div>
        </div>

        <!-- Tabela de Contatos -->
        <div class="contatos-table-wrapper">
            <table class="notion-table contatos-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Telefone</th>
                        <th>CPF</th>
                        <th>Valor Débito</th>
                        <th>Status</th>
                        <th>Tentativas</th>
                        <th>Última Ligação</th>
                    </tr>
                </thead>
                <tbody id="contatosBody">
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem; color: var(--notion-text-secondary);">
                            <i class="fas fa-spinner fa-spin"></i> Carregando contatos...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Paginação -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem;">
            <div style="color: var(--notion-text-secondary); font-size: var(--notion-font-size-sm);">
                Mostrando <span id="contatosFrom">0</span> a <span id="contatosTo">0</span> de <span id="contatosTotal">0</span> contatos
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <button class="notion-btn notion-btn-secondary" onclick="paginaAnterior()" id="btnAnterior">
                    <i class="fas fa-chevron-left"></i> Anterior
                </button>
                <button class="notion-btn notion-btn-secondary" onclick="proximaPagina()" id="btnProxima">
                    Próxima <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Botões de Ação -->
    <div class="notion-form-actions" style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--notion-border);">
        <button type="button" class="notion-btn notion-btn-secondary" onclick="window.history.back()">
            <i class="fas fa-arrow-left"></i> Voltar
        </button>
        <div style="display: flex; gap: 0.5rem;">
            <button type="button" class="notion-btn notion-btn-primary" id="btnAcao" onclick="executarAcao()" style="display: none;">
                <i class="fas fa-play"></i> <span id="textoAcao">Ativar</span>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const campanhaId = '{{ $campanhaId }}';
    let campanhaData = null;
    let contatosList = [];
    let paginaAtual = 1;
    const itensPerPage = 20;
    let contatosFiltrados = [];

    document.addEventListener('DOMContentLoaded', function() {
        carregarCampanha();
    });

    async function carregarCampanha() {
        try {
            const response = await fetchWithAuth(`${API_BASE_URL}/filas_campanha/${campanhaId}`);
            if (!response.ok) {
                throw new Error('Campanha não encontrada');
            }
            campanhaData = await response.json();
            renderizarCampanha();
            carregarContatos();
        } catch (error) {
            console.error('Erro:', error);
            document.getElementById('campanhaInfo').innerHTML = `
                <div class="notion-alert notion-alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>Erro ao carregar campanha: ${error.message}</span>
                </div>
            `;
        }
    }

    function renderizarCampanha() {
        const { data } = campanhaData;
        const statusClass = `status-${data.status}`;
        const progresso = data.stats.progresso || 0;

        const info = document.getElementById('campanhaInfo');
        info.innerHTML = `
            <div class="header-info">
                <div class="info-box">
                    <div class="info-label">Status</div>
                    <div class="info-value ${statusClass}">${capitalize(data.status)}</div>
                </div>
                <div class="info-box">
                    <div class="info-label">Prioridade</div>
                    <div class="info-value">${capitalize(data.prioridade)}</div>
                </div>
                <div class="info-box">
                    <div class="info-label">Taxa de Sucesso</div>
                    <div class="info-value">${data.stats.taxa_sucesso.toFixed(2)}%</div>
                </div>
                <div class="info-box">
                    <div class="info-label">Progresso</div>
                    <div class="info-value">${progresso.toFixed(0)}%</div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${progresso}%"></div>
                    </div>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number">${data.stats.total}</div>
                    <div class="stat-label">Total</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">${data.stats.pendentes}</div>
                    <div class="stat-label">Pendentes</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">${data.stats.processando}</div>
                    <div class="stat-label">Processando</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">${data.stats.completados}</div>
                    <div class="stat-label">Completados</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">${data.stats.falhados}</div>
                    <div class="stat-label">Falhados</div>
                </div>
            </div>

            <div style="margin-top: 1.5rem; padding: 1rem; background: var(--notion-bg-secondary); border-left: 4px solid var(--dvelopers-gold); border-radius: 4px;">
                <div><strong>Descrição:</strong> ${data.descricao || 'Sem descrição'}</div>
                <div style="margin-top: 0.5rem;"><strong>Tipo Público:</strong> ${capitalize(data.tipo_publico || 'N/A')}</div>
                <div style="margin-top: 0.5rem;"><strong>Tentativas Máx:</strong> ${data.max_tentativas}</div>
                <div style="margin-top: 0.5rem;"><strong>Velocidade:</strong> ${data.velocidade_contatos_hora} contatos/hora</div>
            </div>
        `;

        document.getElementById('campanhaDesc').textContent = data.nome;

        // Configurar botão de ação
        const btnAcao = document.getElementById('btnAcao');
        const textoAcao = document.getElementById('textoAcao');

        if (data.status === 'pronto' || data.status === 'pausado') {
            btnAcao.style.display = 'inline-block';
            textoAcao.textContent = data.status === 'pausado' ? 'Retomar' : 'Ativar';
        } else if (data.status === 'ativo') {
            btnAcao.style.display = 'inline-block';
            btnAcao.classList.add('notion-btn-danger');
            textoAcao.textContent = 'Pausar';
        }
    }

    async function carregarContatos() {
        try {
            const response = await fetchWithAuth(`${API_BASE_URL}/filas_campanha/${campanhaId}/contatos`);
            if (!response.ok) {
                throw new Error('Erro ao carregar contatos');
            }
            const data = await response.json();
            contatosList = Array.isArray(data) ? data : (data.data || []);
            contatosFiltrados = [...contatosList];
            renderizarContatos();
        } catch (error) {
            console.error('Erro:', error);
            document.getElementById('contatosBody').innerHTML = `
                <tr>
                    <td colspan="7" style="text-align: center; padding: 2rem; color: var(--notion-text-secondary);">
                        <i class="fas fa-exclamation-circle"></i> Erro ao carregar contatos
                    </td>
                </tr>
            `;
        }
    }

    function renderizarContatos() {
        const inicio = (paginaAtual - 1) * itensPerPage;
        const fim = inicio + itensPerPage;
        const contatosPagina = contatosFiltrados.slice(inicio, fim);

        const tbody = document.getElementById('contatosBody');

        if (contatosPagina.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" style="text-align: center; padding: 2rem; color: var(--notion-text-secondary);">
                        <i class="fas fa-inbox"></i> Nenhum contato encontrado
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = contatosPagina.map(contato => {
            const statusClass = getStatusClass(contato.status);
            const ultimaLigacao = contato.ultima_ligacao
                ? new Date(contato.ultima_ligacao).toLocaleString('pt-BR')
                : 'Nunca';

            return `
                <tr>
                    <td>${contato.nome || 'N/A'}</td>
                    <td>${contato.telefone || 'N/A'}</td>
                    <td>${maskCPF(contato.cpf) || 'N/A'}</td>
                    <td>R$ ${parseFloat(contato.valor_debito || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</td>
                    <td>
                        <span class="status-badge ${statusClass}">
                            ${capitalize(contato.status || 'pendente')}
                        </span>
                    </td>
                    <td>${contato.tentativas || 0}</td>
                    <td>${ultimaLigacao}</td>
                </tr>
            `;
        }).join('');

        // Atualizar informações de paginação
        document.getElementById('contatosFrom').textContent = contatosList.length === 0 ? 0 : inicio + 1;
        document.getElementById('contatosTo').textContent = Math.min(fim, contatosList.length);
        document.getElementById('contatosTotal').textContent = contatosFiltrados.length;

        // Atualizar estado dos botões de paginação
        document.getElementById('btnAnterior').disabled = paginaAtual === 1;
        document.getElementById('btnProxima').disabled = fim >= contatosFiltrados.length;
    }

    function aplicarFiltros() {
        paginaAtual = 1;
        const status = document.getElementById('filtroStatus').value;
        const nome = document.getElementById('filtroNome').value.toLowerCase();

        contatosFiltrados = contatosList.filter(contato => {
            const matchStatus = !status || contato.status === status;
            const matchNome = !nome ||
                (contato.nome && contato.nome.toLowerCase().includes(nome)) ||
                (contato.telefone && contato.telefone.includes(nome));
            return matchStatus && matchNome;
        });

        renderizarContatos();
    }

    function limparFiltros() {
        document.getElementById('filtroStatus').value = '';
        document.getElementById('filtroNome').value = '';
        contatosFiltrados = [...contatosList];
        paginaAtual = 1;
        renderizarContatos();
    }

    function proximaPagina() {
        const totalPaginas = Math.ceil(contatosFiltrados.length / itensPerPage);
        if (paginaAtual < totalPaginas) {
            paginaAtual++;
            renderizarContatos();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    function paginaAnterior() {
        if (paginaAtual > 1) {
            paginaAtual--;
            renderizarContatos();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    async function executarAcao() {
        const acaoMap = {
            'ativo': 'pausar',
            'pausado': 'retomar',
            'pronto': 'ativar'
        };

        const acao = acaoMap[campanhaData.data.status];
        if (!acao) return;

        try {
            const response = await fetchWithAuth(`${API_BASE_URL}/filas_campanha/${campanhaId}/${acao}`, {
                method: 'POST'
            });

            if (response.ok) {
                showNotification(`✅ Campanha ${acao}da com sucesso!`, 'success');
                setTimeout(() => carregarCampanha(), 1500);
            } else {
                const error = await response.json();
                showNotification(error.message || 'Erro ao executar ação', 'error');
            }
        } catch (error) {
            console.error('Erro:', error);
            showNotification('Erro ao executar ação: ' + error.message, 'error');
        }
    }

    function getStatusClass(status) {
        const map = {
            'pendente': 'pendente',
            'processando': 'processando',
            'completado': 'completado',
            'falha': 'falha'
        };
        return map[status] || 'pendente';
    }

    function capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    function maskCPF(cpf) {
        if (!cpf || cpf.length < 11) return cpf;
        return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
    }

    function showNotification(message, type) {
        const notif = document.createElement('div');
        notif.className = `notion-alert notion-alert-${type}`;
        notif.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
        `;
        document.querySelector('.notion-card').prepend(notif);
        setTimeout(() => notif.remove(), 3000);
    }
</script>
@endpush
@endsection
