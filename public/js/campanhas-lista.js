/**
 * campanhas-lista.js
 * Gerencia a listagem de campanhas com filtros, paginação e ações rápidas
 */

let campanhasData = [];
let currentPage = 1;
let totalPages = 1;
let searchTimeout = null;

const STATUS_CONFIG = {
    rascunho: { label: 'Rascunho', bg: 'bg-slate-100', text: 'text-slate-700', dot: 'bg-slate-400' },
    pronto: { label: 'Pronto', bg: 'bg-blue-100', text: 'text-blue-700', dot: 'bg-blue-500' },
    ativo: { label: 'Ativo', bg: 'bg-emerald-100', text: 'text-emerald-700', dot: 'bg-emerald-500' },
    pausado: { label: 'Pausado', bg: 'bg-amber-100', text: 'text-amber-700', dot: 'bg-amber-500' },
    concluido: { label: 'Concluído', bg: 'bg-violet-100', text: 'text-violet-700', dot: 'bg-violet-500' },
    cancelado: { label: 'Cancelado', bg: 'bg-red-100', text: 'text-red-700', dot: 'bg-red-500' },
};

document.addEventListener('DOMContentLoaded', function () {
    loadCampanhas();

    // Debounce search
    document.getElementById('searchInput').addEventListener('input', function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => { currentPage = 1; loadCampanhas(); }, 400);
    });

    document.getElementById('statusFilter').addEventListener('change', function () {
        currentPage = 1;
        loadCampanhas();
    });
});

async function loadCampanhas() {
    const search = document.getElementById('searchInput').value.trim();
    const status = document.getElementById('statusFilter').value;

    let url = `${API_BASE_URL}/filas_campanha?page=${currentPage}`;
    if (search) url += `&search=${encodeURIComponent(search)}`;
    if (status) url += `&status=${encodeURIComponent(status)}`;

    document.getElementById('loadingState').classList.remove('hidden');
    document.getElementById('campanhasGrid').classList.add('hidden');
    document.getElementById('emptyState').classList.add('hidden');
    document.getElementById('paginationContainer').classList.add('hidden');

    try {
        const response = await fetchWithAuth(url);
        const result = await response.json();

        campanhasData = result.data || [];
        const pagination = result.pagination || {};
        currentPage = pagination.current_page || 1;
        totalPages = pagination.last_page || 1;

        updateStats(campanhasData, result);
        renderCampanhas(campanhasData);
        updatePagination(pagination);
    } catch (error) {
        console.error('Erro ao carregar campanhas:', error);
        document.getElementById('loadingState').innerHTML = `
            <i class="fas fa-exclamation-triangle text-2xl text-red-400"></i>
            <p class="mt-3 text-sm text-slate-500">Erro ao carregar campanhas</p>
            <button onclick="loadCampanhas()" class="mt-3 text-sm text-brand-600 hover:text-brand-700 font-medium">Tentar novamente</button>
        `;
    }
}

function updateStats(data, result) {
    // Calcular stats a partir dos dados
    const stats = { total: 0, ativas: 0, pausadas: 0, concluidas: 0 };

    if (result.pagination) {
        stats.total = result.pagination.total || data.length;
    } else {
        stats.total = data.length;
    }

    data.forEach(c => {
        if (c.status === 'ativo') stats.ativas++;
        if (c.status === 'pausado') stats.pausadas++;
        if (c.status === 'concluido') stats.concluidas++;
    });

    document.getElementById('statTotal').textContent = stats.total;
    document.getElementById('statAtivas').textContent = stats.ativas;
    document.getElementById('statPausadas').textContent = stats.pausadas;
    document.getElementById('statConcluidas').textContent = stats.concluidas;
}

function renderCampanhas(campanhas) {
    const grid = document.getElementById('campanhasGrid');
    const loading = document.getElementById('loadingState');
    const empty = document.getElementById('emptyState');

    loading.classList.add('hidden');

    if (!campanhas || campanhas.length === 0) {
        empty.classList.remove('hidden');
        grid.classList.add('hidden');
        return;
    }

    empty.classList.add('hidden');
    grid.classList.remove('hidden');
    grid.innerHTML = campanhas.map(c => renderCard(c)).join('');
}

function renderCard(campanha) {
    const status = STATUS_CONFIG[campanha.status] || STATUS_CONFIG.rascunho;
    const stats = campanha.stats || {};
    const total = stats.total || 0;
    const completados = stats.completados || 0;
    const falhados = stats.falhados || 0;
    const pendentes = stats.pendentes || 0;
    const processando = stats.processando || 0;
    const progresso = campanha.progresso || 0;
    const taxaSucesso = campanha.taxa_sucesso || stats.taxa_sucesso || 0;

    const actionBtn = getActionButton(campanha);

    return `
        <div class="bg-white rounded-xl border border-slate-200 p-5 hover:shadow-md hover:border-slate-300 transition-all duration-200">
            <!-- Header -->
            <div class="flex items-start justify-between mb-3">
                <div class="min-w-0 flex-1">
                    <a href="/campanhas/${campanha.id}" class="text-sm font-semibold text-slate-900 hover:text-brand-600 transition-colors truncate block">
                        ${escapeHtml(campanha.nome || 'Sem nome')}
                    </a>
                    <p class="text-xs text-slate-400 mt-0.5">${campanha.descricao ? escapeHtml(campanha.descricao).substring(0, 60) + (campanha.descricao.length > 60 ? '...' : '') : 'Sem descrição'}</p>
                </div>
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-medium ${status.bg} ${status.text} flex-shrink-0 ml-2">
                    <span class="w-1.5 h-1.5 rounded-full ${status.dot}"></span>
                    ${status.label}
                </span>
            </div>

            <!-- Progress Bar -->
            <div class="mb-3">
                <div class="flex items-center justify-between text-xs mb-1">
                    <span class="text-slate-500">Progresso</span>
                    <span class="font-medium text-slate-700">${Math.round(progresso)}%</span>
                </div>
                <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-500 ${progresso >= 100 ? 'bg-emerald-500' : 'bg-brand-500'}" style="width: ${Math.min(progresso, 100)}%"></div>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-4 gap-2 mb-4">
                <div class="text-center">
                    <p class="text-lg font-bold text-slate-900">${total}</p>
                    <p class="text-[10px] text-slate-400 uppercase">Total</p>
                </div>
                <div class="text-center">
                    <p class="text-lg font-bold text-emerald-600">${completados}</p>
                    <p class="text-[10px] text-slate-400 uppercase">OK</p>
                </div>
                <div class="text-center">
                    <p class="text-lg font-bold text-red-500">${falhados}</p>
                    <p class="text-[10px] text-slate-400 uppercase">Falha</p>
                </div>
                <div class="text-center">
                    <p class="text-lg font-bold text-amber-500">${taxaSucesso.toFixed ? taxaSucesso.toFixed(0) : taxaSucesso}%</p>
                    <p class="text-[10px] text-slate-400 uppercase">Sucesso</p>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2 pt-3 border-t border-slate-100">
                <a href="/campanhas/${campanha.id}" class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-medium text-brand-600 bg-brand-50 hover:bg-brand-100 rounded-lg transition-colors">
                    <i class="fas fa-eye text-xs"></i> Ver Detalhes
                </a>
                ${actionBtn}
            </div>
        </div>
    `;
}

function getActionButton(campanha) {
    const id = campanha.id;
    switch (campanha.status) {
        case 'pronto':
            return `<button onclick="toggleCampanha(${id}, 'ativar')" class="inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-medium text-emerald-600 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition-colors">
                <i class="fas fa-play text-xs"></i> Ativar
            </button>`;
        case 'ativo':
            return `<button onclick="toggleCampanha(${id}, 'pausar')" class="inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-medium text-amber-600 bg-amber-50 hover:bg-amber-100 rounded-lg transition-colors">
                <i class="fas fa-pause text-xs"></i> Pausar
            </button>`;
        case 'pausado':
            return `<button onclick="toggleCampanha(${id}, 'retomar')" class="inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
                <i class="fas fa-play text-xs"></i> Retomar
            </button>`;
        default:
            return '';
    }
}

async function toggleCampanha(id, acao) {
    try {
        const response = await fetchWithAuth(`${API_BASE_URL}/filas_campanha/${id}/${acao}`, { method: 'POST' });
        if (response.ok) {
            if (typeof NotificacaoManager !== 'undefined') {
                NotificacaoManager.sucesso(`Campanha ${acao === 'ativar' ? 'ativada' : acao === 'pausar' ? 'pausada' : 'retomada'} com sucesso`);
            }
            loadCampanhas();
        } else {
            const data = await response.json();
            const msg = data.error || data.message || `Erro ao ${acao} campanha`;
            if (typeof NotificacaoManager !== 'undefined') {
                NotificacaoManager.erro(msg);
            }
        }
    } catch (error) {
        console.error(`Erro ao ${acao} campanha:`, error);
    }
}

function updatePagination(pagination) {
    const container = document.getElementById('paginationContainer');
    if (!pagination || totalPages <= 1) {
        container.classList.add('hidden');
        return;
    }

    container.classList.remove('hidden');
    document.getElementById('paginationInfo').textContent = `Mostrando página ${currentPage} de ${totalPages} (${pagination.total || 0} campanhas)`;
    document.getElementById('pageDisplay').textContent = `${currentPage} / ${totalPages}`;
    document.getElementById('prevBtn').disabled = currentPage <= 1;
    document.getElementById('nextBtn').disabled = currentPage >= totalPages;
}

function changePage(delta) {
    const newPage = currentPage + delta;
    if (newPage >= 1 && newPage <= totalPages) {
        currentPage = newPage;
        loadCampanhas();
    }
}

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}
