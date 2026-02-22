@extends('layouts.app')

@section('title', 'Status de Importações - URA Dvelopers')

@section('content')
<!-- Page Header -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900">Status de Importa&ccedil;&otilde;es</h1>
    <p class="text-slate-500 text-sm mt-1">Visualize o hist&oacute;rico e status das importa&ccedil;&otilde;es de contatos</p>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <!-- Filters -->
    <div class="p-4 border-b border-slate-100">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Status</label>
                <select class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500" id="statusFilter" onchange="applyFilters()">
                    <option value="">Todos</option>
                    <option value="processing">Processando</option>
                    <option value="completed">Conclu&iacute;do</option>
                    <option value="failed">Falha</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Buscar por Campanha</label>
                <input type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500" id="searchFilter" placeholder="Nome da campanha..." onchange="applyFilters()">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Data Inicial</label>
                <input type="date" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500" id="dateStart" onchange="applyFilters()">
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
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200" id="importacoesTable">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Campanha</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Data</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Contatos</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Sucesso</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">A&ccedil;&otilde;es</th>
                </tr>
            </thead>
            <tbody id="importacoesBody" class="bg-white divide-y divide-slate-100">
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-slate-400">
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

document.addEventListener('DOMContentLoaded', function() {
    loadImportacoes();
});

async function loadImportacoes() {
    try {
        const response = await fetchWithAuth(`${API_BASE_URL}/filas_campanha?page=${currentPage}`);
        const data = await response.json();
        renderTable(data);
    } catch (error) {
        console.error('Erro:', error);
    }
}

function renderTable(data) {
    const tbody = document.getElementById('importacoesBody');
    if (!data.data || data.data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-12 text-center text-slate-400">Nenhuma importa\u00e7\u00e3o encontrada</td></tr>';
        return;
    }

    tbody.innerHTML = data.data.map(item => {
        const badgeClass = item.status === 'pronto' ? 'bg-emerald-100 text-emerald-700'
            : item.status === 'ativo' ? 'bg-amber-100 text-amber-700'
            : item.status === 'concluido' ? 'bg-emerald-100 text-emerald-700'
            : 'bg-red-100 text-red-700';
        return `
            <tr class="hover:bg-slate-50 transition-colors">
                <td class="px-6 py-3 text-sm text-slate-500">#${item.id}</td>
                <td class="px-6 py-3 text-sm text-slate-900 font-medium">${item.nome}</td>
                <td class="px-6 py-3 text-sm text-slate-500">${new Date(item.created_at).toLocaleDateString('pt-BR')}</td>
                <td class="px-6 py-3 text-sm text-slate-600">${item.stats.total}</td>
                <td class="px-6 py-3"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${badgeClass}">${item.status}</span></td>
                <td class="px-6 py-3 text-sm text-slate-600">${item.stats.completados}/${item.stats.total}</td>
                <td class="px-6 py-3"><button class="px-3 py-1 bg-white border border-slate-300 hover:bg-slate-50 text-slate-600 rounded-lg text-xs transition-colors" onclick="viewDetails(${item.id})">Ver</button></td>
            </tr>
        `;
    }).join('');

    document.getElementById('pageNum').textContent = currentPage;
}

function applyFilters() { currentPage = 1; loadImportacoes(); }
function clearFilters() {
    document.getElementById('statusFilter').value = '';
    document.getElementById('searchFilter').value = '';
    document.getElementById('dateStart').value = '';
    applyFilters();
}
function previousPage() { if (currentPage > 1) { currentPage--; loadImportacoes(); } }
function nextPage() { currentPage++; loadImportacoes(); }
function viewDetails(id) { window.location.href = '/campanha/importacao'; }
</script>
@endpush
