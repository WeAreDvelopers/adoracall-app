@extends('layouts.app')

@section('title', 'Status da Fila - URA Dvelopers')

@section('content')
<!-- Page Header -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900">Fila de Chamadas</h1>
    <p class="text-slate-500 text-sm mt-1">Acompanhe o status dos itens na fila de processamento</p>
</div>

<!-- Metrics -->
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total na Fila</p>
        <p class="text-xl font-bold text-slate-900 mt-1" id="totalQueue">0</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs font-medium text-blue-500 uppercase tracking-wide">Pendentes</p>
        <p class="text-xl font-bold text-slate-900 mt-1" id="pendingCount">0</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs font-medium text-amber-500 uppercase tracking-wide">Processando</p>
        <p class="text-xl font-bold text-slate-900 mt-1" id="processingCount">0</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs font-medium text-emerald-600 uppercase tracking-wide">Conclu&iacute;dos</p>
        <p class="text-xl font-bold text-slate-900 mt-1" id="completedCount">0</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs font-medium text-red-500 uppercase tracking-wide">Com Falha</p>
        <p class="text-xl font-bold text-slate-900 mt-1" id="failedCount">0</p>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl border border-slate-200 p-4 mb-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Status</label>
            <select class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500" id="statusFilter" onchange="applyFilters()">
                <option value="">Todos</option>
                <option value="pending">Pendente</option>
                <option value="processing">Processando</option>
                <option value="completed">Conclu&iacute;do</option>
                <option value="failed">Falha</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Buscar</label>
            <input type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500" id="searchFilter" placeholder="ID ou Campanha..." onchange="applyFilters()">
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
        <table class="min-w-full divide-y divide-slate-200" id="filaTable">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Contato</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Tentativas</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Criado em</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">A&ccedil;&otilde;es</th>
                </tr>
            </thead>
            <tbody id="filaBody" class="bg-white divide-y divide-slate-100">
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                        <i class="fas fa-spinner fa-spin text-lg"></i>
                        <p class="mt-2 text-sm">Carregando...</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="px-6 py-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
        <span class="text-sm text-slate-500">P&aacute;gina <span id="pageNum" class="font-medium">1</span></span>
        <div class="flex gap-2">
            <button class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-600 rounded-lg text-sm transition-colors" onclick="previousPage()" id="prevBtn">
                <i class="fas fa-chevron-left mr-1"></i> Anterior
            </button>
            <button class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-600 rounded-lg text-sm transition-colors" onclick="nextPage()" id="nextBtn">
                Pr&oacute;xima <i class="fas fa-chevron-right ml-1"></i>
            </button>
        </div>
        <label class="flex items-center gap-2 text-xs text-slate-500">
            <input type="checkbox" id="autoRefresh" checked class="rounded border-slate-300 text-brand-500 focus:ring-brand-500">
            Auto-refresh
            <input type="number" id="refreshInterval" value="5" min="2" max="30" class="w-12 px-2 py-1 border border-slate-300 rounded text-xs text-center">
            seg
        </label>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentPage = 1;
let refreshInterval = null;

document.addEventListener('DOMContentLoaded', function() {
    loadFilaData();

    document.getElementById('autoRefresh').addEventListener('change', function() {
        if (this.checked) startAutoRefresh();
        else stopAutoRefresh();
    });

    if (document.getElementById('autoRefresh').checked) startAutoRefresh();
});

async function loadFilaData() {
    try {
        const status = document.getElementById('statusFilter').value;
        const search = document.getElementById('searchFilter').value;
        const dateStart = document.getElementById('dateStart').value;
        const dateEnd = document.getElementById('dateEnd').value;

        let url = `${API_BASE_URL}/filas_campanha/fila-stats?page=${currentPage + 1}`;
        if (status) url += `&status=${encodeURIComponent(status)}`;
        if (search) url += `&search=${encodeURIComponent(search)}`;
        if (dateStart) url += `&date_start=${encodeURIComponent(dateStart)}`;
        if (dateEnd) url += `&date_end=${encodeURIComponent(dateEnd)}`;

        const response = await fetchWithAuth(url);
        const data = await response.json();
        renderFilaTable(data);
        updateMetrics(data);
    } catch (error) {
        console.error('Erro ao carregar fila:', error);
    }
}

function renderFilaTable(data) {
    const tbody = document.getElementById('filaBody');
    if (!data.data || data.data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-12 text-center text-slate-400"><i class="fas fa-inbox text-xl"></i><p class="mt-2 text-sm">Nenhum item encontrado</p></td></tr>';
        return;
    }

    tbody.innerHTML = data.data.map(item => {
        const queueJob = item.queue_jobs && item.queue_jobs.length > 0 ? item.queue_jobs[0] : null;
        const status = queueJob ? queueJob.status : item.status || 'N/A';
        const tentativas = queueJob ? queueJob.tentativas : 0;

        const badgeClass = {
            pending: 'bg-blue-100 text-blue-700',
            processing: 'bg-amber-100 text-amber-700',
            completed: 'bg-emerald-100 text-emerald-700',
            failed: 'bg-red-100 text-red-700'
        }[status] || 'bg-slate-100 text-slate-600';

        return `
            <tr class="hover:bg-slate-50 transition-colors">
                <td class="px-6 py-3 text-sm text-slate-500">#${item.id}</td>
                <td class="px-6 py-3 text-sm text-slate-900 font-medium">${item.nome || 'N/A'}</td>
                <td class="px-6 py-3"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${badgeClass}">${translateStatus(status)}</span></td>
                <td class="px-6 py-3 text-sm text-slate-600">${tentativas || 0}</td>
                <td class="px-6 py-3 text-sm text-slate-500">${new Date(item.created_at).toLocaleDateString('pt-BR', { year: 'numeric', month: '2-digit', day: '2-digit' })}</td>
                <td class="px-6 py-3"><button class="px-3 py-1 bg-white border border-slate-300 hover:bg-slate-50 text-slate-600 rounded-lg text-xs transition-colors" onclick="viewDetails(${item.id})">Ver</button></td>
            </tr>
        `;
    }).join('');

    if (data.pagination) {
        document.getElementById('prevBtn').disabled = data.pagination.current_page <= 1;
        document.getElementById('nextBtn').disabled = data.pagination.current_page >= data.pagination.last_page;
        document.getElementById('pageNum').textContent = data.pagination.current_page;
    }
}

function translateStatus(status) {
    const translations = { 'pending': 'Pendente', 'processing': 'Processando', 'completed': 'Conclu\u00eddo', 'failed': 'Falha' };
    return translations[status] || status;
}

function updateMetrics(data) {
    const stats = data.stats || {};
    document.getElementById('totalQueue').textContent = stats.total || 0;
    document.getElementById('pendingCount').textContent = stats.pending || 0;
    document.getElementById('processingCount').textContent = stats.processing || 0;
    document.getElementById('completedCount').textContent = stats.completed || 0;
    document.getElementById('failedCount').textContent = stats.failed || 0;
}

function getStatusClass(status) {
    return { pending: 'info', processing: 'warning', completed: 'success', failed: 'danger' }[status] || 'info';
}

function applyFilters() { currentPage = 1; loadFilaData(); }
function clearFilters() {
    document.getElementById('statusFilter').value = '';
    document.getElementById('searchFilter').value = '';
    document.getElementById('dateStart').value = '';
    document.getElementById('dateEnd').value = '';
    applyFilters();
}
function previousPage() { if (currentPage > 1) { currentPage--; loadFilaData(); } }
function nextPage() { currentPage++; loadFilaData(); }
function startAutoRefresh() { const interval = parseInt(document.getElementById('refreshInterval').value) * 1000; refreshInterval = setInterval(loadFilaData, interval); }
function stopAutoRefresh() { clearInterval(refreshInterval); }
function viewDetails(id) { console.log('Ver detalhes do item:', id); }
</script>
@endpush
