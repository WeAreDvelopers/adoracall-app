@extends('layouts.app')

@section('title', 'Status de Ligações - AdoraCall')

@section('content')
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Hist&oacute;rico de Chamadas</h1>
        <p class="text-slate-500 text-sm mt-1">Acompanhe o hist&oacute;rico e m&eacute;tricas de liga&ccedil;&otilde;es realizadas</p>
    </div>

    <!-- Metrics -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total de Liga&ccedil;&otilde;es</p>
            <p class="text-2xl font-bold text-slate-900 mt-1" id="totalCalls">0</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-medium text-emerald-600 uppercase tracking-wide">Bem-Sucedidas</p>
            <p class="text-2xl font-bold text-slate-900 mt-1" id="successCalls">0</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-medium text-red-500 uppercase tracking-wide">Falhadas</p>
            <p class="text-2xl font-bold text-slate-900 mt-1" id="failedCalls">0</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-medium text-blue-500 uppercase tracking-wide">Dura&ccedil;&atilde;o M&eacute;dia</p>
            <p class="text-2xl font-bold text-slate-900 mt-1" id="avgDuration">0s</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-slate-200 p-4 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Status</label>
                <select class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500" id="statusFilter" onchange="applyFilters()">
                    <option value="">Todos</option>
                    <option value="ended">Finalizado</option>
                    <option value="in_progress">Em Andamento</option>
                    <option value="error">Erro</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Buscar Telefone</label>
                <input type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500" id="phoneFilter" placeholder="Telefone..." onchange="applyFilters()">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Data Inicial</label>
                <input type="date" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500" id="dateStart" onchange="applyFilters()">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Data Final</label>
                <input type="date" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500" id="dateEnd" onchange="applyFilters()">
            </div>
            <div class="flex gap-2">
                <button class="flex-1 px-3 py-2 bg-brand-500 hover:bg-brand-600 text-white font-medium rounded-lg text-sm transition-colors" onclick="applyFilters()">
                    <i class="fas fa-search"></i> Filtrar
                </button>
                <button class="px-3 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-600 rounded-lg text-sm transition-colors" onclick="clearFilters()">
                    <i class="fas fa-undo"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200" id="ligacoesTable">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Telefone</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Cliente</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Dura&ccedil;&atilde;o</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Sentimento</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Data</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">A&ccedil;&otilde;es</th>
                    </tr>
                </thead>
                <tbody id="ligacoesBody" class="bg-white divide-y divide-slate-100">
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-slate-400">
                            <i class="fas fa-spinner fa-spin text-lg"></i>
                            <p class="mt-2 text-sm">Carregando...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between">
            <span class="text-sm text-slate-500">P&aacute;gina <span id="pageNum" class="font-medium">1</span></span>
            <div class="flex gap-2">
                <button class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-600 rounded-lg text-sm transition-colors" onclick="previousPage()">
                    <i class="fas fa-chevron-left mr-1"></i> Anterior
                </button>
                <button class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-600 rounded-lg text-sm transition-colors" onclick="nextPage()">
                    Pr&oacute;xima <i class="fas fa-chevron-right ml-1"></i>
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let currentPage = 1;

        function getAudioType(url) {
            if (!url) return 'audio/mpeg';
            const extension = url.split('.').pop().toLowerCase();
            const audioTypes = { 'mp3': 'audio/mpeg', 'wav': 'audio/wav', 'ogg': 'audio/ogg', 'm4a': 'audio/mp4', 'flac': 'audio/flac', 'webm': 'audio/webm' };
            return audioTypes[extension] || 'audio/mpeg';
        }

        document.addEventListener('DOMContentLoaded', function() {
            loadLigacoes();
        });

        async function loadLigacoes() {
            try {
                const status = document.getElementById('statusFilter').value;
                const phone = document.getElementById('phoneFilter').value;
                const dateStart = document.getElementById('dateStart').value;
                const dateEnd = document.getElementById('dateEnd').value;

                let url = `${API_BASE_URL}/filas_campanha/ligacoes/stats?page=${currentPage}`;
                if (status) url += `&status=${encodeURIComponent(status)}`;
                if (phone) url += `&phone=${encodeURIComponent(phone)}`;
                if (dateStart) url += `&date_start=${encodeURIComponent(dateStart)}`;
                if (dateEnd) url += `&date_end=${encodeURIComponent(dateEnd)}`;

                const response = await fetchWithAuth(url);
                const data = await response.json();
                renderTable(data);
                updateMetrics(data);
            } catch (error) {
                console.error('Erro:', error);
            }
        }

        function renderTable(data) {
            const tbody = document.getElementById('ligacoesBody');
            const ligacoes = data.ligacoes_paginadas && data.ligacoes_paginadas.data ? data.ligacoes_paginadas.data : [];

            if (!ligacoes || ligacoes.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="px-6 py-12 text-center text-slate-400"><i class="fas fa-inbox text-xl"></i><p class="mt-2 text-sm">Nenhuma liga\u00e7\u00e3o encontrada</p></td></tr>';
                return;
            }

            tbody.innerHTML = ligacoes.map(item => {
                const callHistory = item.call_history;
                const callId = item.call_id_retell || item.id;
                const telefone = item.contato ? item.contato.telefone : item.telefone || 'N/A';
                const cliente = item.contato ? item.contato.nome : 'N/A';
                const duracao = callHistory?.duration_ms ? (callHistory.duration_ms / 1000).toFixed(0) : (item.duracao || 0);
                const status = callHistory?.call_status || item.status || 'registered';
                const badgeClass = (status === 'ended' || status === 'completed') ? 'bg-emerald-100 text-emerald-700'
                    : (status === 'error' || status === 'failed') ? 'bg-red-100 text-red-700'
                    : 'bg-amber-100 text-amber-700';
                const statusLabel = (status === 'ended' || status === 'completed') ? 'Finalizada'
                    : (status === 'error' || status === 'failed') ? 'Erro' : 'Registrada';
                const sentimento = callHistory?.user_sentiment || item.user_sentiment || '-';

                return `
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-3 text-sm text-slate-500">#${item.id}</td>
                        <td class="px-6 py-3 text-sm text-slate-600 font-mono">${telefone}</td>
                        <td class="px-6 py-3 text-sm text-slate-900 font-medium">${cliente}</td>
                        <td class="px-6 py-3 text-sm text-slate-600">${duracao > 0 ? duracao + 's' : '-'}</td>
                        <td class="px-6 py-3 text-sm text-slate-600">${sentimento}</td>
                        <td class="px-6 py-3"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${badgeClass}">${statusLabel}</span></td>
                        <td class="px-6 py-3 text-sm text-slate-500">${new Date(item.created_at).toLocaleDateString('pt-BR', { year: 'numeric', month: '2-digit', day: '2-digit' })}</td>
                        <td class="px-6 py-3"><button class="px-3 py-1 bg-white border border-slate-300 hover:bg-slate-50 text-slate-600 rounded-lg text-xs transition-colors" onclick="viewDetails('${callId}', '${item.id}')">Ver</button></td>
                    </tr>
                `;
            }).join('');

            if (data.ligacoes_paginadas && data.ligacoes_paginadas.pagination) {
                document.getElementById('pageNum').textContent = data.ligacoes_paginadas.pagination.current_page;
            }
        }

        function updateMetrics(data) {
            const stats = data.estatisticas_gerais || {};
            const resumo = stats.resumo || {};
            const progresso = stats.progresso || {};

            document.getElementById('totalCalls').textContent = resumo.total_ligacoes || 0;
            document.getElementById('successCalls').textContent = progresso.sucesso || 0;
            document.getElementById('failedCalls').textContent = progresso.falhas || 0;
            document.getElementById('avgDuration').textContent = resumo.duracao_media_geral ? Math.round(resumo.duracao_media_geral) + 's' : '0s';
        }

        function applyFilters() { currentPage = 1; loadLigacoes(); }
        function clearFilters() {
            document.getElementById('statusFilter').value = '';
            document.getElementById('phoneFilter').value = '';
            document.getElementById('dateStart').value = '';
            document.getElementById('dateEnd').value = '';
            applyFilters();
        }
        function previousPage() { if (currentPage > 1) { currentPage--; loadLigacoes(); } }
        function nextPage() { currentPage++; loadLigacoes(); }

        async function viewDetails(callId, ligacaoId) {
            try {
                const url = `${API_BASE_URL}/ura/history/call/${callId}`;
                const response = await fetchWithAuth(url);
                const data = await response.json();

                if (data.success && data.data) {
                    showDetailsModal(data.data, callId);
                } else {
                    alert('Erro ao carregar detalhes da liga\u00e7\u00e3o');
                }
            } catch (error) {
                console.error('Erro ao carregar detalhes:', error);
                alert('Erro ao carregar detalhes da liga\u00e7\u00e3o');
            }
        }

        function showDetailsModal(callData, callId) {
            const localData = callData.local_data || {};
            const enhanced = callData.enhanced_data || {};
            const analysis = callData.call_analysis || {};
            const callSummary = analysis.call_summary || {};

            const modal = document.createElement('div');
            modal.id = 'detailsModal';
            modal.className = 'fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4';

            const content = document.createElement('div');
            content.className = 'bg-white rounded-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto shadow-2xl';

            content.innerHTML = `
                <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center sticky top-0 bg-white rounded-t-2xl z-10">
                    <h2 class="text-lg font-bold text-slate-900">
                        <i class="fas fa-phone text-slate-400 mr-2"></i>Detalhes da Liga\u00e7\u00e3o
                    </h2>
                    <button onclick="closeModal()" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-colors text-xl">&times;</button>
                </div>

                <div class="p-6 space-y-6">
                    ${localData && localData.cliente_nome ? `
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                        <h3 class="text-sm font-semibold text-blue-900 mb-3"><i class="fas fa-user mr-2"></i>Informa\u00e7\u00f5es do Cliente</h3>
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div><span class="text-blue-600">Nome:</span> <span class="font-medium text-blue-900">${localData.cliente_nome || '-'}</span></div>
                            <div><span class="text-blue-600">Empresa:</span> <span class="font-medium text-blue-900">${localData.empresa_credora || '-'}</span></div>
                            <div><span class="text-blue-600">Valor:</span> <span class="font-medium text-blue-900">R$ ${parseFloat(localData.valor_devido || 0).toFixed(2)}</span></div>
                            <div><span class="text-blue-600">Vencimento:</span> <span class="font-medium text-blue-900">${localData.vencimento || '-'}</span></div>
                        </div>
                    </div>
                    ` : ''}

                    <div>
                        <h3 class="text-sm font-semibold text-slate-900 mb-3"><i class="fas fa-info-circle text-slate-400 mr-2"></i>Informa\u00e7\u00f5es da Liga\u00e7\u00e3o</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="bg-slate-50 rounded-lg p-3">
                                <p class="text-[11px] uppercase text-slate-400 font-medium">Call ID</p>
                                <p class="text-sm font-medium text-slate-900 break-all mt-0.5">${callId || 'N/A'}</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-3">
                                <p class="text-[11px] uppercase text-slate-400 font-medium">Status</p>
                                <p class="text-sm font-medium text-slate-900 mt-0.5">${callData.call_status || 'N/A'}</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-3">
                                <p class="text-[11px] uppercase text-slate-400 font-medium">Dura\u00e7\u00e3o</p>
                                <p class="text-sm font-medium text-slate-900 mt-0.5">${enhanced.duration_formatted || (callSummary.duration_ms ? (callSummary.duration_ms / 1000).toFixed(0) + 's' : 'N/A')}</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold text-slate-900 mb-3"><i class="fas fa-phone-alt text-slate-400 mr-2"></i>N\u00fameros</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-slate-50 rounded-lg p-3">
                                <p class="text-[11px] uppercase text-slate-400 font-medium">De</p>
                                <p class="text-sm font-mono font-medium text-slate-900 mt-0.5">${callData.from_number || 'N/A'}</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-3">
                                <p class="text-[11px] uppercase text-slate-400 font-medium">Para</p>
                                <p class="text-sm font-mono font-medium text-slate-900 mt-0.5">${callData.to_number || 'N/A'}</p>
                            </div>
                        </div>
                    </div>

                    ${callData.user_sentiment ? `
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900 mb-3"><i class="fas fa-chart-pie text-slate-400 mr-2"></i>An\u00e1lise</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-slate-50 rounded-lg p-3">
                                <p class="text-[11px] uppercase text-slate-400 font-medium">Sentimento</p>
                                <p class="text-sm font-medium text-slate-900 mt-0.5">${callData.user_sentiment || 'N/A'}</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-3">
                                <p class="text-[11px] uppercase text-slate-400 font-medium">Sucesso</p>
                                <p class="text-sm font-medium text-slate-900 mt-0.5">${callData.call_successful ? '\u2713 Sim' : '\u2717 N\u00e3o'}</p>
                            </div>
                        </div>
                    </div>
                    ` : ''}

                    ${callSummary.summary ? `
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900 mb-3"><i class="fas fa-file-alt text-slate-400 mr-2"></i>Resumo</h3>
                        <div class="bg-slate-50 rounded-lg p-4 text-sm text-slate-700 leading-relaxed">${callSummary.summary}</div>
                    </div>
                    ` : ''}

                    ${callData.transcript ? `
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900 mb-3"><i class="fas fa-closed-captioning text-slate-400 mr-2"></i>Transcri\u00e7\u00e3o</h3>
                        <div class="bg-slate-50 rounded-lg p-4 text-xs text-slate-700 leading-relaxed max-h-64 overflow-y-auto">${callData.transcript.split('\\n').join('<br />')}</div>
                    </div>
                    ` : ''}

                    ${callData.recording_url ? `
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900 mb-3"><i class="fas fa-microphone text-slate-400 mr-2"></i>Grava\u00e7\u00e3o</h3>
                        <audio controls class="w-full rounded-lg" preload="metadata" crossorigin="anonymous">
                            <source src="${callData.recording_url}" type="${getAudioType(callData.recording_url)}">
                            <source src="${callData.recording_url}" type="audio/wav">
                            <source src="${callData.recording_url}" type="audio/mpeg">
                            Seu navegador n\u00e3o suporta o elemento de \u00e1udio.
                        </audio>
                    </div>
                    ` : ''}

                    <div>
                        <h3 class="text-sm font-semibold text-slate-900 mb-3"><i class="fas fa-clock text-slate-400 mr-2"></i>Datas e Hor\u00e1rios</h3>
                        <div class="grid grid-cols-2 gap-3">
                            ${callData.start_timestamp ? `
                            <div class="bg-slate-50 rounded-lg p-3">
                                <p class="text-[11px] uppercase text-slate-400 font-medium">In\u00edcio</p>
                                <p class="text-xs font-medium text-slate-900 mt-0.5">${new Date(callData.start_timestamp).toLocaleString('pt-BR')}</p>
                            </div>
                            ` : ''}
                            ${callData.end_timestamp ? `
                            <div class="bg-slate-50 rounded-lg p-3">
                                <p class="text-[11px] uppercase text-slate-400 font-medium">T\u00e9rmino</p>
                                <p class="text-xs font-medium text-slate-900 mt-0.5">${new Date(callData.end_timestamp).toLocaleString('pt-BR')}</p>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 rounded-b-2xl flex justify-end">
                    <button onclick="closeModal()" class="px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-medium rounded-lg text-sm transition-colors">
                        <i class="fas fa-times mr-1"></i> Fechar
                    </button>
                </div>
            `;

            modal.appendChild(content);
            document.body.appendChild(modal);

            modal.onclick = function(event) {
                if (event.target === modal) closeModal();
            };
        }

        function closeModal() {
            const modal = document.getElementById('detailsModal');
            if (modal) modal.remove();
        }
    </script>
@endpush
