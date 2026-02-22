@extends('layouts.app')

@section('title', 'Gerenciar Filas - URA Dvelopers')

@section('content')
    <style>
        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            color: var(--notion-text);
        }

        .page-header p {
            color: var(--notion-text-secondary);
            font-size: 0.95rem;
        }

        .controls-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .control-card {
            background: var(--notion-bg);
            border: 1px solid var(--notion-border);
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .control-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .control-card-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--notion-text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .control-card-header i {
            font-size: 0.9rem;
            opacity: 0.7;
        }

        .controls-bar-left {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.65rem 1rem;
            border: none;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .btn-primary {
            background: var(--dvelopers-gold);
            color: white;
        }

        .btn-primary:hover {
            background: var(--dvelopers-gold-dark);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(255, 174, 0, 0.3);
        }

        .btn-secondary {
            background: var(--notion-bg-secondary);
            color: var(--notion-text);
            border: 1px solid var(--notion-border);
        }

        .btn-secondary:hover {
            background: var(--notion-border);
            transform: translateY(-1px);
        }

        .filters {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }

        .filter-group label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--notion-text);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .filter-group select {
            padding: 0.65rem 0.75rem;
            border: 1px solid var(--notion-border);
            border-radius: 6px;
            background: var(--notion-bg-secondary);
            color: var(--notion-text);
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .filter-group select:hover {
            border-color: var(--dvelopers-gold);
        }

        .filter-group select:focus {
            outline: none;
            border-color: var(--dvelopers-gold);
            box-shadow: 0 0 0 2px rgba(255, 174, 0, 0.1);
        }

        .refresh-indicator {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .refresh-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .refresh-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--notion-border);
            transition: all 0.3s ease;
        }

        .refresh-dot.active {
            background: var(--dvelopers-gold);
            box-shadow: 0 0 8px var(--dvelopers-gold);
        }

        .refresh-time {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }

        .refresh-time-label {
            font-size: 0.75rem;
            color: var(--notion-text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }

        .refresh-time-value {
            font-size: 0.9rem;
            color: var(--notion-text);
            font-weight: 500;
            font-variant-numeric: tabular-nums;
        }

        .table-container {
            background: var(--notion-bg);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
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

        .status-badge {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            white-space: nowrap;
        }

        .status-ativo {
            background: #c8e6c9;
            color: #1b5e20;
        }

        .status-pausado {
            background: #fff9c4;
            color: #f57f17;
        }

        .status-pronto {
            background: #bbdefb;
            color: #01579b;
        }

        .status-concluido {
            background: #a5d6a7;
            color: #1b5e20;
        }

        .status-cancelado {
            background: #ffcdd2;
            color: #b71c1c;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 0.75rem;
        }

        .stat {
            text-align: center;
            padding: 0.75rem;
            background: var(--notion-bg-secondary);
            border-radius: 4px;
        }

        .stat-label {
            font-size: 0.75rem;
            color: var(--notion-text-secondary);
            margin-bottom: 0.25rem;
        }

        .stat-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--notion-text);
        }

        .stat-pending {
            color: #f57f17;
        }

        .stat-processing {
            color: #1976d2;
        }

        .stat-completed {
            color: #388e3c;
        }

        .stat-failed {
            color: #d32f2f;
        }

        .progress-container {
            width: 100%;
            height: 6px;
            background: var(--notion-border);
            border-radius: 3px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--dvelopers-gold), #ffd700);
            transition: width 0.3s;
        }

        .row-controls {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-action {
            padding: 0.5rem 0.75rem;
            border: none;
            border-radius: 4px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            position: relative;
        }

        .btn-action:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-action.loading::after {
            content: '';
            display: inline-block;
            width: 12px;
            height: 12px;
            margin-left: 4px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .btn-action-start {
            background: #4caf50;
            color: white;
        }

        .btn-action-start:hover:not(:disabled) {
            background: #45a049;
            box-shadow: 0 2px 8px rgba(76, 175, 80, 0.3);
        }

        .btn-action-pause {
            background: #ff9800;
            color: white;
        }

        .btn-action-pause:hover:not(:disabled) {
            background: #e68900;
            box-shadow: 0 2px 8px rgba(255, 152, 0, 0.3);
        }

        .btn-action-resume {
            background: #2196f3;
            color: white;
        }

        .btn-action-resume:hover:not(:disabled) {
            background: #0b7dda;
            box-shadow: 0 2px 8px rgba(33, 150, 243, 0.3);
        }

        .btn-action-retry {
            background: #9c27b0;
            color: white;
        }

        .btn-action-retry:hover:not(:disabled) {
            background: #7b1fa2;
            box-shadow: 0 2px 8px rgba(156, 39, 176, 0.3);
        }

        .btn-action-stop {
            background: #f44336;
            color: white;
        }

        .btn-action-stop:hover:not(:disabled) {
            background: #da190b;
            box-shadow: 0 2px 8px rgba(244, 67, 54, 0.3);
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--notion-text-secondary);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state h3 {
            margin-bottom: 0.5rem;
            color: var(--notion-text);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: var(--notion-bg);
            padding: 2rem;
            border-radius: 8px;
            max-width: 400px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .modal-content h2 {
            margin-bottom: 1rem;
            color: var(--notion-text);
        }

        .modal-content p {
            margin-bottom: 1.5rem;
            color: var(--notion-text-secondary);
        }

        .modal-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
        }

        .modal-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .modal-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .modal-btn.loading::after {
            content: '';
            display: inline-block;
            width: 12px;
            height: 12px;
            margin-left: 4px;
            border: 2px solid rgba(0, 0, 0, 0.3);
            border-top-color: currentColor;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        .modal-btn-cancel {
            background: var(--notion-bg-secondary);
            color: var(--notion-text);
        }

        .modal-btn-cancel:hover:not(:disabled) {
            background: var(--notion-border);
        }

        .modal-btn-confirm {
            background: #f44336;
            color: white;
        }

        .modal-btn-confirm:hover:not(:disabled) {
            background: #da190b;
            box-shadow: 0 2px 8px rgba(244, 67, 54, 0.3);
        }

        footer {
            text-align: center;
            padding: 1.5rem;
            margin-top: 2rem;
            color: var(--notion-text-secondary);
            font-size: 0.85rem;
            border-top: 1px solid var(--notion-border);
        }

        @media (max-width: 1024px) {
            .stats {
                grid-template-columns: repeat(3, 1fr);
            }

            .controls-bar {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 1.4rem;
            }

            .controls-bar {
                grid-template-columns: 1fr;
            }

            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .row-controls {
                flex-direction: column;
            }

            .btn-action {
                width: 100%;
                justify-content: center;
            }

            .control-card {
                padding: 1.2rem;
            }

            .controls-bar-left {
                gap: 0.5rem;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .refresh-indicator {
                flex-direction: column;
                gap: 1rem;
            }

            .refresh-time {
                width: 100%;
            }
        }
    </style>
    <!-- Page Header -->
    <div class="page-header">
        <h1>📊 Gerenciamento de Filas</h1>
        <p>Controle individual de cada campanha/fila de processamento</p>
    </div>

    <!-- Controls Bar -->
    <div class="controls-bar">
        <!-- Ações Rápidas -->
        <div class="control-card">
            <div class="control-card-header">
                <i class="fas fa-bolt"></i>
                Ações Rápidas
            </div>
            <div class="controls-bar-left">
                <button class="btn btn-primary" onclick="loadQueues()">
                    <i class="fas fa-sync"></i> Atualizar
                </button>
                <button class="btn btn-secondary" onclick="toggleAutoRefresh()">
                    <i class="fas fa-clock"></i> Auto-refresh: <strong id="autoRefreshStatus">OFF</strong>
                </button>
            </div>
        </div>

        <!-- Filtros -->
        <div class="control-card">
            <div class="control-card-header">
                <i class="fas fa-filter"></i>
                Filtros
            </div>
            <div class="filters">
                <div class="filter-group">
                    <label>Por Status</label>
                    <select id="statusFilter" onchange="applyFilters()">
                        <option value="">Todos</option>
                        <option value="ativo">Ativo</option>
                        <option value="pausado">Pausado</option>
                        <option value="pronto">Pronto</option>
                        <option value="concluido">Concluído</option>
                        <option value="cancelado">Cancelado</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Status Sincronização -->
        <div class="control-card">
            <div class="control-card-header">
                <i class="fas fa-circle-notch"></i>
                Sincronização
            </div>
            <div class="refresh-indicator">
                <div class="refresh-status">
                    <div class="refresh-dot" id="refreshDot"></div>
                    <span style="font-size: 0.9rem; color: var(--notion-text); font-weight: 500;">
                        <span id="syncStatus">Aguardando</span>
                    </span>
                </div>
                <div class="refresh-time">
                    <div class="refresh-time-label">Última atualização</div>
                    <div class="refresh-time-value" id="lastUpdate">Nunca</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="table-container">
        <table class="notion-table" id="queuesTable">
            <thead>
                <tr>
                    <th style="width: 20%;">Campanha</th>
                    <th style="width: 12%;">Status</th>
                    <th style="width: 28%;">Estatísticas</th>
                    <th style="width: 15%;">Progresso</th>
                    <th style="width: 25%;">Ações</th>
                </tr>
            </thead>
            <tbody id="queuesBody">
                <tr>
                    <td colspan="5" class="empty-state">
                        <i class="fas fa-hourglass-start"></i>
                        <h3>Carregando filas...</h3>
                        <p>Por favor, aguarde</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <!-- <footer>
        <p>&copy; 2026 We Are Dvelopers. Todos os direitos reservados.</p>
    </footer> -->

    <!-- Confirmation Modal -->
    <div class="modal" id="confirmModal">
        <div class="modal-content">
            <h2 id="modalTitle">Confirmar Ação</h2>
            <p id="modalMessage">Tem certeza?</p>
            <div class="modal-actions">
                <button class="modal-btn modal-btn-cancel" onclick="closeModal()">
                    Cancelar
                </button>
                <button class="modal-btn modal-btn-confirm" onclick="confirmAction()">
                    Confirmar
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let autoRefreshInterval = null;
        let queues = [];
        let pendingAction = null;

        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            loadQueues();
        });

        // Carregar filas
        async function loadQueues() {
            try {
                const response = await fetchWithAuth(`${API_BASE_URL}/filas_campanha`);
                if (!response.ok) throw new Error('Erro ao buscar filas');

                const data = await response.json();
                // Adaptar para nova estrutura de dados
                queues = data.data || data.filas || [];

                renderQueues();
                updateLastUpdateTime();
            } catch (error) {
                console.error('Erro ao carregar filas:', error);
                if (typeof feedback !== 'undefined') {
                    feedback.error('Erro ao carregar filas');
                }
                showEmptyState('Erro ao carregar filas', error.message);
            }
        }

        // Renderizar tabela de filas
        function renderQueues() {
            const tbody = document.getElementById('queuesBody');

            if (!queues || queues.length === 0) {
                showEmptyState('Nenhuma fila encontrada', 'Crie uma campanha para começar');
                return;
            }

            let html = '';

            queues.forEach(queue => {
                const stats = queue.stats || {};
                const total = stats.total || 0;
                const processados = stats.processados || 0;
                const progresso = total > 0 ? Math.round((processados / total) * 100) : 0;
                const taxaSucesso = stats.taxa_sucesso || 0;

                html += `
                <tr>
                    <td>
                        <div style="font-weight: 600; margin-bottom: 4px;">${queue.nome}</div>
                    </td>
                    <td>
                        <span class="status-badge status-${queue.status}">
                            ${getStatusIcon(queue.status)} ${getStatusLabel(queue.status)}
                        </span>
                    </td>
                    <td>
                        <div class="stats">
                            <div class="stat">
                                <div class="stat-label">Total</div>
                                <div class="stat-value">${stats.total || 0}</div>
                            </div>
                            <div class="stat">
                                <div class="stat-label">Pendente</div>
                                <div class="stat-value stat-pending">${stats.pendentes || 0}</div>
                            </div>
                            <div class="stat">
                                <div class="stat-label">Processando</div>
                                <div class="stat-value stat-processing">${stats.processando || 0}</div>
                            </div>
                            <div class="stat">
                                <div class="stat-label">Completo</div>
                                <div class="stat-value stat-completed">${stats.completados || 0}</div>
                            </div>
                            <div class="stat">
                                <div class="stat-label">Falha</div>
                                <div class="stat-value stat-failed">${stats.falhados || 0}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="margin-bottom: 8px; font-size: 12px;">
                            ${progresso}% (${processados}/${total})
                        </div>
                        <div class="progress-container">
                            <div class="progress-bar" style="width: ${progresso}%"></div>
                        </div>
                        <div style="font-size: 11px; color: #999; margin-top: 4px;">
                            Taxa sucesso: <strong>${taxaSucesso.toFixed(1)}%</strong>
                        </div>
                    </td>
                    <td>
                        <div class="row-controls">
                            ${renderActionButtons(queue)}
                        </div>
                    </td>
                </tr>
            `;
            });

            tbody.innerHTML = html;
        }

        // Renderizar botões de ação
        function renderActionButtons(queue) {
            console.log('Renderizando botões para fila:', queue);
            const mailingId = queue.id;
            const hasFalhas = queue.stats && queue.stats.falhados > 0;
            let buttons = '';

            // Botões de controle de status
            if (queue.status === 'pronto' || queue.status === 'rascunho') {
                buttons +=
                    `<button class="btn-action btn-action-start" id="btn-ativar-${mailingId}" onclick="activateMailing(${mailingId})"><i class="fas fa-play"></i> Ativar</button>`;
            } else if (queue.status === 'ativo') {
                buttons +=
                    `<button class="btn-action btn-action-pause" id="btn-pausar-${mailingId}" onclick="pauseMailing(${mailingId})"><i class="fas fa-pause"></i> Pausar</button>`;
            } else if (queue.status === 'pausado') {
                buttons +=
                    `<button class="btn-action btn-action-resume" id="btn-retomar-${mailingId}" onclick="resumeMailing(${mailingId})"><i class="fas fa-play"></i> Retomar</button>`;
            }

            // Botão Reprocessar (quando há falhas)
            if (hasFalhas && queue.status !== 'cancelado') {
                buttons +=
                    `<button class="btn-action btn-action-retry" id="btn-reprocessar-${mailingId}" onclick="reprocessarMailing(${mailingId})"><i class="fas fa-redo"></i> Reprocessar</button>`;
            }

            // Botão Parar
            if (queue.status !== 'concluido' && queue.status !== 'cancelado') {
                buttons +=
                    `<button class="btn-action btn-action-stop" id="btn-parar-${mailingId}" onclick="showStopConfirm(${mailingId}, '${queue.nome}')"><i class="fas fa-stop"></i> Parar</button>`;
            }

            return buttons;
        }

        // Função auxiliar para desabilitar botões
        function setButtonLoading(mailingId, action, isLoading) {
            const buttonId = `btn-${action}-${mailingId}`;
            const button = document.getElementById(buttonId);
            if (button) {
                button.disabled = isLoading;
                if (isLoading) {
                    button.classList.add('loading');
                } else {
                    button.classList.remove('loading');
                }
            }
        }

        // Ações de Mailing
        async function activateMailing(mailingId) {
            try {
                setButtonLoading(mailingId, 'ativar', true);

                const response = await fetchWithAuth(`${API_BASE_URL}/filas_campanha/${mailingId}/ativar`, {
                    method: 'POST'
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.error || errorData.message || 'Erro ao ativar campanha');
                }

                const data = await response.json();
                console.log('✅ [ATIVAR] Resposta:', data);

                if (typeof feedback !== 'undefined') {
                    feedback.success(`Campanha ativada! ${data.jobs_criados || 0} jobs criados.`);
                }

                await loadQueues();
            } catch (error) {
                console.error('❌ [ATIVAR] Erro:', error);
                if (typeof feedback !== 'undefined') {
                    feedback.error(error.message || 'Erro ao ativar campanha');
                }
            } finally {
                setButtonLoading(mailingId, 'ativar', false);
            }
        }

        async function pauseMailing(mailingId) {
            try {
                setButtonLoading(mailingId, 'pausar', true);

                const response = await fetchWithAuth(`${API_BASE_URL}/filas_campanha/${mailingId}/pausar`, {
                    method: 'POST'
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.error || errorData.message || 'Erro ao pausar campanha');
                }

                const data = await response.json();
                console.log('✅ [PAUSAR] Resposta:', data);

                if (typeof feedback !== 'undefined') {
                    feedback.success('Campanha pausada!');
                }

                await loadQueues();
            } catch (error) {
                console.error('❌ [PAUSAR] Erro:', error);
                if (typeof feedback !== 'undefined') {
                    feedback.error(error.message || 'Erro ao pausar campanha');
                }
            } finally {
                setButtonLoading(mailingId, 'pausar', false);
            }
        }

        async function resumeMailing(mailingId) {
            try {
                setButtonLoading(mailingId, 'retomar', true);

                const response = await fetchWithAuth(`${API_BASE_URL}/filas_campanha/${mailingId}/retomar`, {
                    method: 'POST'
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.error || errorData.message || 'Erro ao retomar campanha');
                }

                const data = await response.json();
                console.log('✅ [RETOMAR] Resposta:', data);

                if (typeof feedback !== 'undefined') {
                    feedback.success(`Campanha retomada! ${data.jobs_criados || 0} jobs criados.`);
                }

                await loadQueues();
            } catch (error) {
                console.error('❌ [RETOMAR] Erro:', error);
                if (typeof feedback !== 'undefined') {
                    feedback.error(error.message || 'Erro ao retomar campanha');
                }
            } finally {
                setButtonLoading(mailingId, 'retomar', false);
            }
        }

        async function reprocessarMailing(mailingId) {
            try {
                setButtonLoading(mailingId, 'reprocessar', true);

                const response = await fetchWithAuth(`${API_BASE_URL}/filas_campanha/${mailingId}/retry-falhas`, {
                    method: 'POST'
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.error || errorData.message || 'Erro ao reprocessar campanha');
                }

                const data = await response.json();
                console.log('✅ [REPROCESSAR] Resposta:', data);

                if (typeof feedback !== 'undefined') {
                    feedback.success(`Reprocessamento iniciado! ${data.total || 0} jobs resetados para retry.`);
                }

                await loadQueues();
            } catch (error) {
                console.error('❌ [REPROCESSAR] Erro:', error);
                if (typeof feedback !== 'undefined') {
                    feedback.error(error.message || 'Erro ao reprocessar campanha');
                }
            } finally {
                setButtonLoading(mailingId, 'reprocessar', false);
            }
        }

        function showStopConfirm(mailingId, mailingName) {
            document.getElementById('modalTitle').textContent = 'Cancelar Campanha?';
            document.getElementById('modalMessage').textContent =
                `Deseja cancelar a campanha "${mailingName}"? Isso irá parar o processamento da fila.`;
            pendingAction = {
                action: 'cancel',
                mailingId
            };
            document.getElementById('confirmModal').classList.add('active');
        }

        async function cancelMailing(mailingId) {
            try {
                setButtonLoading(mailingId, 'parar', true);

                const response = await fetchWithAuth(`${API_BASE_URL}/filas_campanha/${mailingId}/cancelar`, {
                    method: 'POST'
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.error || errorData.message || 'Erro ao cancelar campanha');
                }

                const data = await response.json();
                console.log('✅ [CANCELAR] Resposta:', data);

                if (typeof feedback !== 'undefined') {
                    feedback.success('Campanha cancelada!');
                }

                await loadQueues();
            } catch (error) {
                console.error('❌ [CANCELAR] Erro:', error);
                if (typeof feedback !== 'undefined') {
                    feedback.error(error.message || 'Erro ao cancelar campanha');
                }
            } finally {
                setButtonLoading(mailingId, 'parar', false);
            }
        }

        // Modal
        async function confirmAction() {
            if (pendingAction && pendingAction.action === 'cancel') {
                // Desabilitar botão de confirmação durante a ação
                const confirmBtn = document.querySelector('.modal-btn-confirm');
                const cancelBtn = document.querySelector('.modal-btn-cancel');
                if (confirmBtn) confirmBtn.disabled = true;
                if (cancelBtn) cancelBtn.disabled = true;

                // Executar ação
                await cancelMailing(pendingAction.mailingId);

                // Reabilitar botões e fechar modal
                if (confirmBtn) confirmBtn.disabled = false;
                if (cancelBtn) cancelBtn.disabled = false;
                closeModal();
            }
        }

        function closeModal() {
            document.getElementById('confirmModal').classList.remove('active');
            pendingAction = null;
        }

        // Auto-refresh
        function toggleAutoRefresh() {
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
                autoRefreshInterval = null;
                document.getElementById('autoRefreshStatus').textContent = 'OFF';
                document.getElementById('refreshDot').classList.remove('active');
                if (typeof feedback !== 'undefined') {
                    feedback.info('Auto-refresh desativado');
                }
            } else {
                autoRefreshInterval = setInterval(loadQueues, 5000);
                document.getElementById('autoRefreshStatus').textContent = 'ON';
                document.getElementById('refreshDot').classList.add('active');
                if (typeof feedback !== 'undefined') {
                    feedback.success('Auto-refresh ativado (5s)');
                }
            }
        }

        function updateLastUpdateTime() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');

            document.getElementById('lastUpdate').textContent = `${hours}:${minutes}:${seconds}`;

            // Atualizar status de sincronização
            const syncStatus = document.getElementById('syncStatus');
            if (syncStatus) {
                syncStatus.textContent = autoRefreshInterval ? 'Sincronizando' : 'Aguardando';
            }
        }

        // Filtros
        function applyFilters() {
            const statusFilter = document.getElementById('statusFilter').value;
            const rows = document.querySelectorAll('#queuesBody tr');

            rows.forEach(row => {
                if (!statusFilter) {
                    row.style.display = '';
                    return;
                }

                const statusCell = row.querySelector('.status-badge');
                if (statusCell && statusCell.className.includes(`status-${statusFilter}`)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Helpers
        function getStatusIcon(status) {
            const icons = {
                'ativo': '🟢',
                'pausado': '🟡',
                'pronto': '🔵',
                'concluido': '✅',
                'cancelado': '❌'
            };
            return icons[status] || '❓';
        }

        function getStatusLabel(status) {
            const labels = {
                'ativo': 'Ativo',
                'pausado': 'Pausado',
                'pronto': 'Pronto',
                'concluido': 'Concluído',
                'cancelado': 'Cancelado'
            };
            return labels[status] || status;
        }

        function showEmptyState(title, message) {
            const tbody = document.getElementById('queuesBody');
            tbody.innerHTML = `
            <tr>
                <td colspan="5" class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>${title}</h3>
                    <p>${message}</p>
                </td>
            </tr>
        `;
        }
    </script>
@endpush
