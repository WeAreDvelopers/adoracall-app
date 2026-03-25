@extends('layouts.app')

@section('title', 'Gerenciar Filas - AdoraCall')

@section('content')
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Gerenciamento de Filas</h1>
            <p class="text-slate-500 text-sm mt-1">Controle individual de cada campanha/fila de processamento</p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="toggleAutoRefresh()"
                class="inline-flex items-center gap-2 px-3 py-2 text-sm text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors border border-slate-300">
                <i class="fas fa-clock"></i>
                Auto-refresh: <strong id="autoRefreshStatus">OFF</strong>
            </button>
            <button onclick="loadQueues()"
                class="inline-flex items-center gap-2 bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2.5 rounded-lg transition-colors shadow-sm">
                <i class="fas fa-sync-alt"></i>
                <span>Atualizar</span>
            </button>
        </div>
    </div>

    <!-- Controls Bar -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <!-- Filtro por Status -->
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <label class="block text-xs font-medium text-slate-600 mb-1.5">Filtrar por Status</label>
            <select id="statusFilter" onchange="applyFilters()"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                <option value="">Todos os status</option>
                <option value="ativo">Ativo</option>
                <option value="pausado">Pausado</option>
                <option value="pronto">Pronto</option>
                <option value="concluido">Concluído</option>
                <option value="cancelado">Cancelado</option>
            </select>
        </div>

        <!-- Sincronização -->
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Sincronização</p>
            <div class="flex items-center gap-2 mt-1.5">
                <span class="w-2 h-2 rounded-full bg-slate-300 transition-all" id="refreshDot"></span>
                <span class="text-sm font-medium text-slate-900" id="syncStatus">Aguardando</span>
            </div>
        </div>

        <!-- Última Atualização -->
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Última Atualização</p>
            <p class="text-2xl font-bold text-slate-900 mt-1 tabular-nums" id="lastUpdate">Nunca</p>
        </div>
    </div>

    <!-- Loading -->
    <div id="loadingState" class="bg-white rounded-xl border border-slate-200 p-12 text-center">
        <i class="fas fa-spinner fa-spin text-2xl text-brand-500"></i>
        <p class="mt-3 text-sm text-slate-500">Carregando filas...</p>
    </div>

    <!-- Empty State -->
    <div id="emptyState" class="hidden bg-white rounded-xl border border-slate-200 p-12 text-center">
        <i class="fas fa-inbox text-4xl text-slate-300"></i>
        <p class="mt-3 text-base font-medium text-slate-600">Nenhuma fila encontrada</p>
        <p class="mt-1 text-sm text-slate-400">Crie uma campanha para começar</p>
    </div>

    <!-- Table -->
    <div id="tableContainer" class="hidden bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full" id="queuesTable">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50">
                        <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wide px-5 py-3" style="width: 20%;">Campanha</th>
                        <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wide px-5 py-3" style="width: 12%;">Status</th>
                        <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wide px-5 py-3" style="width: 28%;">Estatísticas</th>
                        <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wide px-5 py-3" style="width: 15%;">Progresso</th>
                        <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wide px-5 py-3" style="width: 25%;">Ações</th>
                    </tr>
                </thead>
                <tbody id="queuesBody" class="divide-y divide-slate-100">
                </tbody>
            </table>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="hidden fixed inset-0 bg-black/50 z-50 items-center justify-center" id="confirmModal">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4 p-6">
            <h2 class="text-lg font-bold text-slate-900 mb-2" id="modalTitle">Confirmar Ação</h2>
            <p class="text-sm text-slate-500 mb-6" id="modalMessage">Tem certeza?</p>
            <div class="flex items-center justify-end gap-3">
                <button class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors" onclick="closeModal()">
                    Cancelar
                </button>
                <button class="px-4 py-2 text-sm font-medium text-white bg-red-500 hover:bg-red-600 rounded-lg transition-colors" id="confirmBtn" onclick="confirmAction()">
                    Confirmar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Contatos -->
    <div class="hidden fixed inset-0 bg-black/50 z-50 items-center justify-center" id="contatosModal">
        <div class="bg-white rounded-xl shadow-xl max-w-3xl w-[90%] max-h-[85vh] mx-4 p-6 flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-slate-900" id="contatosModalTitle">Contatos</h2>
                <button class="text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg p-1.5 transition-colors" onclick="closeContatosModal()">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <div class="flex flex-wrap gap-3 mb-4">
                <input type="text" id="contatosBusca" placeholder="Buscar por nome, telefone ou CPF..."
                    class="flex-1 min-w-[200px] px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500"
                    onkeydown="if(event.key==='Enter') loadContatos()">
                <select id="contatosStatusFilter" onchange="loadContatos()"
                    class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                    <option value="">Todos os status</option>
                    <option value="pendente">Pendente</option>
                    <option value="em_ligacao">Em ligação</option>
                    <option value="finalizado">Finalizado</option>
                    <option value="acordo_firmado">Acordo firmado</option>
                    <option value="sem_resposta">Sem resposta</option>
                </select>
            </div>
            <div class="overflow-y-auto flex-1 min-h-[200px]" id="contatosBody">
                <div class="text-center py-8 text-slate-500 text-sm">Carregando contatos...</div>
            </div>
            <div class="flex items-center justify-between pt-3 border-t border-slate-200 mt-3 text-sm text-slate-500" id="contatosPagination" style="display:none;">
                <span id="contatosInfo"></span>
                <div class="flex items-center gap-2">
                    <button id="contatosPrev" onclick="loadContatos(contatosCurrentPage - 1)"
                        class="px-3 py-1.5 text-sm border border-slate-300 rounded-lg hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        Anterior
                    </button>
                    <button id="contatosNext" onclick="loadContatos(contatosCurrentPage + 1)"
                        class="px-3 py-1.5 text-sm border border-slate-300 rounded-lg hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        Próxima
                    </button>
                </div>
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
            const loadingState = document.getElementById('loadingState');
            const emptyState = document.getElementById('emptyState');
            const tableContainer = document.getElementById('tableContainer');

            loadingState.classList.add('hidden');

            if (!queues || queues.length === 0) {
                emptyState.classList.remove('hidden');
                tableContainer.classList.add('hidden');
                return;
            }

            emptyState.classList.add('hidden');
            tableContainer.classList.remove('hidden');

            let html = '';

            queues.forEach(queue => {
                const stats = queue.stats || {};
                const total = stats.total || 0;
                const processados = stats.processados || 0;
                const progresso = total > 0 ? Math.round((processados / total) * 100) : 0;
                const taxaSucesso = stats.taxa_sucesso || 0;

                html += `
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-5 py-4">
                        <span class="font-semibold text-sm text-slate-900">${queue.nome}</span>
                    </td>
                    <td class="px-5 py-4">
                        ${getStatusBadge(queue.status)}
                    </td>
                    <td class="px-5 py-4">
                        <div class="grid grid-cols-5 gap-2">
                            <div class="text-center">
                                <p class="text-[10px] font-medium text-slate-400 uppercase">Total</p>
                                <p class="text-sm font-bold text-slate-900">${stats.total || 0}</p>
                            </div>
                            <div class="text-center">
                                <p class="text-[10px] font-medium text-amber-500 uppercase">Pendente</p>
                                <p class="text-sm font-bold text-slate-900">${stats.pendentes || 0}</p>
                            </div>
                            <div class="text-center">
                                <p class="text-[10px] font-medium text-blue-500 uppercase">Processando</p>
                                <p class="text-sm font-bold text-slate-900">${stats.processando || 0}</p>
                            </div>
                            <div class="text-center">
                                <p class="text-[10px] font-medium text-emerald-600 uppercase">Completo</p>
                                <p class="text-sm font-bold text-slate-900">${stats.completados || 0}</p>
                            </div>
                            <div class="text-center">
                                <p class="text-[10px] font-medium text-red-500 uppercase">Falha</p>
                                <p class="text-sm font-bold text-slate-900">${stats.falhados || 0}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4">
                        <div class="text-xs text-slate-600 mb-1.5 font-medium">${progresso}% (${processados}/${total})</div>
                        <div class="w-full h-1.5 bg-slate-200 rounded-full overflow-hidden">
                            <div class="h-full bg-brand-500 rounded-full transition-all" style="width: ${progresso}%"></div>
                        </div>
                        <div class="text-[11px] text-slate-400 mt-1">Taxa sucesso: <strong class="text-slate-600">${taxaSucesso.toFixed(1)}%</strong></div>
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex flex-wrap gap-1.5">
                            ${renderActionButtons(queue)}
                        </div>
                    </td>
                </tr>
            `;
            });

            tbody.innerHTML = html;
        }

        // Status badge com Tailwind
        function getStatusBadge(status) {
            const config = {
                'ativo': { bg: 'bg-emerald-50', text: 'text-emerald-700', ring: 'ring-emerald-600/20', icon: 'fa-circle', label: 'Ativo' },
                'pausado': { bg: 'bg-amber-50', text: 'text-amber-700', ring: 'ring-amber-600/20', icon: 'fa-pause-circle', label: 'Pausado' },
                'pronto': { bg: 'bg-blue-50', text: 'text-blue-700', ring: 'ring-blue-600/20', icon: 'fa-check-circle', label: 'Pronto' },
                'concluido': { bg: 'bg-slate-50', text: 'text-slate-700', ring: 'ring-slate-600/20', icon: 'fa-flag-checkered', label: 'Concluído' },
                'cancelado': { bg: 'bg-red-50', text: 'text-red-700', ring: 'ring-red-600/20', icon: 'fa-times-circle', label: 'Cancelado' },
            };
            const c = config[status] || { bg: 'bg-slate-50', text: 'text-slate-700', ring: 'ring-slate-600/20', icon: 'fa-question-circle', label: status };
            return `<span data-status="${status}" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium ${c.bg} ${c.text} ring-1 ring-inset ${c.ring}">
                <i class="fas ${c.icon} text-[10px]"></i> ${c.label}
            </span>`;
        }

        // Renderizar botões de ação
        function renderActionButtons(queue) {
            const mailingId = queue.id;
            const hasFalhas = queue.stats && queue.stats.falhados > 0;
            let buttons = '';

            const btnBase = 'inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md text-xs font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed';

            if (queue.status === 'pronto' || queue.status === 'rascunho') {
                buttons += `<button class="${btnBase} bg-emerald-500 hover:bg-emerald-600 text-white" id="btn-ativar-${mailingId}" onclick="activateMailing(${mailingId})"><i class="fas fa-play text-[10px]"></i> Ativar</button>`;
            } else if (queue.status === 'ativo') {
                buttons += `<button class="${btnBase} bg-amber-500 hover:bg-amber-600 text-white" id="btn-pausar-${mailingId}" onclick="pauseMailing(${mailingId})"><i class="fas fa-pause text-[10px]"></i> Pausar</button>`;
            } else if (queue.status === 'pausado') {
                buttons += `<button class="${btnBase} bg-blue-500 hover:bg-blue-600 text-white" id="btn-retomar-${mailingId}" onclick="resumeMailing(${mailingId})"><i class="fas fa-play text-[10px]"></i> Retomar</button>`;
            }

            if (hasFalhas && queue.status !== 'cancelado') {
                buttons += `<button class="${btnBase} bg-purple-500 hover:bg-purple-600 text-white" id="btn-reprocessar-${mailingId}" onclick="reprocessarMailing(${mailingId})"><i class="fas fa-redo text-[10px]"></i> Reprocessar</button>`;
            }

            if (queue.status !== 'cancelado') {
                buttons += `<button class="${btnBase} bg-teal-500 hover:bg-teal-600 text-white" id="btn-reiniciar-${mailingId}" onclick="showReiniciarConfirm(${mailingId}, '${queue.nome}')"><i class="fas fa-sync-alt text-[10px]"></i> Reiniciar</button>`;
            }

            if (queue.status !== 'concluido' && queue.status !== 'cancelado') {
                buttons += `<button class="${btnBase} bg-red-500 hover:bg-red-600 text-white" id="btn-parar-${mailingId}" onclick="showStopConfirm(${mailingId}, '${queue.nome}')"><i class="fas fa-stop text-[10px]"></i> Parar</button>`;
            }

            buttons += `<button class="${btnBase} bg-slate-500 hover:bg-slate-600 text-white" onclick="openContatosModal(${mailingId}, '${queue.nome}')"><i class="fas fa-users text-[10px]"></i> Contatos</button>`;

            return buttons;
        }

        // Função auxiliar para desabilitar botões
        function setButtonLoading(mailingId, action, isLoading) {
            const buttonId = `btn-${action}-${mailingId}`;
            const button = document.getElementById(buttonId);
            if (button) {
                button.disabled = isLoading;
                if (isLoading) {
                    const icon = button.querySelector('i');
                    if (icon) {
                        icon.className = 'fas fa-spinner fa-spin text-[10px]';
                    }
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
                console.log('[ATIVAR] Resposta:', data);

                if (typeof feedback !== 'undefined') {
                    feedback.success(`Campanha ativada! ${data.jobs_criados || 0} jobs criados.`);
                }

                await loadQueues();
            } catch (error) {
                console.error('[ATIVAR] Erro:', error);
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
                console.log('[PAUSAR] Resposta:', data);

                if (typeof feedback !== 'undefined') {
                    feedback.success('Campanha pausada!');
                }

                await loadQueues();
            } catch (error) {
                console.error('[PAUSAR] Erro:', error);
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
                console.log('[RETOMAR] Resposta:', data);

                if (typeof feedback !== 'undefined') {
                    feedback.success(`Campanha retomada! ${data.jobs_criados || 0} jobs criados.`);
                }

                await loadQueues();
            } catch (error) {
                console.error('[RETOMAR] Erro:', error);
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
                console.log('[REPROCESSAR] Resposta:', data);

                if (typeof feedback !== 'undefined') {
                    feedback.success(`Reprocessamento iniciado! ${data.total || 0} jobs resetados para retry.`);
                }

                await loadQueues();
            } catch (error) {
                console.error('[REPROCESSAR] Erro:', error);
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
            pendingAction = { action: 'cancel', mailingId };
            const modal = document.getElementById('confirmModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
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
                console.log('[CANCELAR] Resposta:', data);

                if (typeof feedback !== 'undefined') {
                    feedback.success('Campanha cancelada!');
                }

                await loadQueues();
            } catch (error) {
                console.error('[CANCELAR] Erro:', error);
                if (typeof feedback !== 'undefined') {
                    feedback.error(error.message || 'Erro ao cancelar campanha');
                }
            } finally {
                setButtonLoading(mailingId, 'parar', false);
            }
        }

        function showReiniciarConfirm(mailingId, mailingName) {
            document.getElementById('modalTitle').textContent = 'Reiniciar Fila?';
            document.getElementById('modalMessage').textContent =
                `Deseja reiniciar a fila "${mailingName}"? Todos os jobs serão resetados para pendente e a campanha será reativada. Contatos com acordo firmado não serão afetados.`;
            pendingAction = { action: 'reiniciar', mailingId };
            const modal = document.getElementById('confirmModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        async function reiniciarMailing(mailingId) {
            try {
                setButtonLoading(mailingId, 'reiniciar', true);

                const response = await fetchWithAuth(`${API_BASE_URL}/filas_campanha/${mailingId}/reiniciar`, {
                    method: 'POST'
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.error || errorData.message || 'Erro ao reiniciar fila');
                }

                const data = await response.json();
                console.log('[REINICIAR] Resposta:', data);

                if (typeof feedback !== 'undefined') {
                    feedback.success(`Fila reiniciada! ${data.jobs_resetados || 0} jobs resetados, ${data.total_pending || 0} pendentes.`);
                }

                await loadQueues();
            } catch (error) {
                console.error('[REINICIAR] Erro:', error);
                if (typeof feedback !== 'undefined') {
                    feedback.error(error.message || 'Erro ao reiniciar fila');
                }
            } finally {
                setButtonLoading(mailingId, 'reiniciar', false);
            }
        }

        // Modal
        async function confirmAction() {
            if (!pendingAction) return;

            const confirmBtn = document.getElementById('confirmBtn');
            if (confirmBtn) confirmBtn.disabled = true;

            if (pendingAction.action === 'cancel') {
                await cancelMailing(pendingAction.mailingId);
            } else if (pendingAction.action === 'reiniciar') {
                await reiniciarMailing(pendingAction.mailingId);
            }

            if (confirmBtn) confirmBtn.disabled = false;
            closeModal();
        }

        function closeModal() {
            const modal = document.getElementById('confirmModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            pendingAction = null;
        }

        // Auto-refresh
        function toggleAutoRefresh() {
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
                autoRefreshInterval = null;
                document.getElementById('autoRefreshStatus').textContent = 'OFF';
                document.getElementById('refreshDot').classList.remove('bg-brand-500', 'shadow-[0_0_8px_theme(colors.brand.500)]');
                document.getElementById('refreshDot').classList.add('bg-slate-300');
                if (typeof feedback !== 'undefined') {
                    feedback.info('Auto-refresh desativado');
                }
            } else {
                autoRefreshInterval = setInterval(loadQueues, 5000);
                document.getElementById('autoRefreshStatus').textContent = 'ON';
                document.getElementById('refreshDot').classList.remove('bg-slate-300');
                document.getElementById('refreshDot').classList.add('bg-brand-500', 'shadow-[0_0_8px_theme(colors.brand.500)]');
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

                const badge = row.querySelector('[data-status]');
                if (badge && badge.dataset.status === statusFilter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Helpers
        function showEmptyState(title, message) {
            document.getElementById('loadingState').classList.add('hidden');
            document.getElementById('tableContainer').classList.add('hidden');
            const emptyState = document.getElementById('emptyState');
            emptyState.classList.remove('hidden');
            emptyState.querySelector('.text-base').textContent = title;
            emptyState.querySelector('.text-sm').textContent = message;
        }

        // ===== Modal de Contatos =====
        let contatosMailingId = null;
        let contatosCurrentPage = 1;

        function openContatosModal(mailingId, mailingName) {
            contatosMailingId = mailingId;
            contatosCurrentPage = 1;
            document.getElementById('contatosModalTitle').textContent = `Contatos - ${mailingName}`;
            document.getElementById('contatosBusca').value = '';
            document.getElementById('contatosStatusFilter').value = '';
            const modal = document.getElementById('contatosModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            loadContatos();
        }

        function closeContatosModal() {
            const modal = document.getElementById('contatosModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            contatosMailingId = null;
        }

        async function loadContatos(page = 1) {
            if (!contatosMailingId) return;
            contatosCurrentPage = page;

            const body = document.getElementById('contatosBody');
            body.innerHTML = '<div class="text-center py-8 text-slate-500 text-sm"><i class="fas fa-spinner fa-spin mr-2"></i>Carregando contatos...</div>';
            document.getElementById('contatosPagination').style.display = 'none';

            const busca = document.getElementById('contatosBusca').value;
            const status = document.getElementById('contatosStatusFilter').value;

            let url = `${API_BASE_URL}/filas_campanha/${contatosMailingId}/contatos?page=${page}&per_page=15`;
            if (busca) url += `&buscar=${encodeURIComponent(busca)}`;
            if (status) url += `&status=${encodeURIComponent(status)}`;

            try {
                const response = await fetchWithAuth(url);
                if (!response.ok) throw new Error('Erro ao buscar contatos');

                const data = await response.json();
                renderContatos(data);
            } catch (error) {
                console.error('[CONTATOS] Erro:', error);
                body.innerHTML = `<div class="text-center py-8 text-red-500 text-sm">Erro ao carregar contatos: ${error.message}</div>`;
            }
        }

        function renderContatos(data) {
            const body = document.getElementById('contatosBody');
            const contatos = data.data || [];

            if (contatos.length === 0) {
                body.innerHTML = '<div class="text-center py-8 text-slate-500 text-sm">Nenhum contato encontrado</div>';
                document.getElementById('contatosPagination').style.display = 'none';
                return;
            }

            const statusConfig = {
                'pendente': 'bg-blue-50 text-blue-700',
                'em_ligacao': 'bg-amber-50 text-amber-700',
                'finalizado': 'bg-emerald-50 text-emerald-700',
                'acordo_firmado': 'bg-emerald-100 text-emerald-800',
                'falha': 'bg-red-50 text-red-700',
                'sem_resposta': 'bg-pink-50 text-pink-700',
            };

            let html = `<table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50">
                        <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wide px-4 py-2.5 sticky top-0 bg-slate-50 z-10">Nome</th>
                        <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wide px-4 py-2.5 sticky top-0 bg-slate-50 z-10">Telefone</th>
                        <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wide px-4 py-2.5 sticky top-0 bg-slate-50 z-10">Valor Débito</th>
                        <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wide px-4 py-2.5 sticky top-0 bg-slate-50 z-10">Status</th>
                        <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wide px-4 py-2.5 sticky top-0 bg-slate-50 z-10">Tentativas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">`;

            contatos.forEach(c => {
                const statusClass = (c.status || '').replace(/\s+/g, '_');
                const classes = statusConfig[statusClass] || 'bg-slate-50 text-slate-700';
                const valor = c.valor_debito ? parseFloat(c.valor_debito).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) : '-';
                html += `
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-2.5 text-slate-900">${c.nome || '-'} ${c.sobrenome || ''}</td>
                        <td class="px-4 py-2.5 text-slate-600">${c.telefone || '-'}</td>
                        <td class="px-4 py-2.5 text-slate-600">${valor}</td>
                        <td class="px-4 py-2.5"><span class="inline-block px-2 py-0.5 rounded text-xs font-medium ${classes}">${c.status || '-'}</span></td>
                        <td class="px-4 py-2.5 text-slate-600">${c.tentativas || 0}</td>
                    </tr>`;
            });

            html += '</tbody></table>';
            body.innerHTML = html;

            // Paginação
            const pagination = document.getElementById('contatosPagination');
            const from = data.from || 0;
            const to = data.to || 0;
            const total = data.total || 0;

            document.getElementById('contatosInfo').textContent = `${from}-${to} de ${total} contatos`;
            document.getElementById('contatosPrev').disabled = !data.prev_page_url;
            document.getElementById('contatosNext').disabled = !data.next_page_url;
            pagination.style.display = total > 0 ? 'flex' : 'none';
        }

        // Fechar modais clicando fora
        document.getElementById('contatosModal').addEventListener('click', function(e) {
            if (e.target === this) closeContatosModal();
        });
        document.getElementById('confirmModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
@endpush
