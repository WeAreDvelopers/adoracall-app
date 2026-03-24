@extends('layouts.app')

@section('title', 'Home - AdoraCall')

@section('content')

<!-- Page Header -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900">Bem-vindo ao Sistema de URA</h1>
    <p class="text-slate-500 mt-1">Visão geral do sistema</p>
</div>

<!-- Big Numbers - KPIs -->
<div class="grid grid-cols-2 md:grid-cols-3 2xl:grid-cols-6 gap-4 mb-8">
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <div class="flex items-center gap-2 mb-2">
            <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
                <i class="fas fa-bullhorn text-blue-500 text-sm"></i>
            </div>
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Campanhas Ativas</p>
        </div>
        <p class="text-2xl font-bold text-blue-600 whitespace-nowrap" id="stat-campanhas">
            <span class="inline-block w-12 h-7 bg-slate-100 rounded animate-pulse"></span>
        </p>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <div class="flex items-center gap-2 mb-2">
            <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center">
                <i class="fas fa-phone text-emerald-500 text-sm"></i>
            </div>
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Chamadas Hoje</p>
        </div>
        <p class="text-2xl font-bold text-emerald-600 whitespace-nowrap" id="stat-chamadas">
            <span class="inline-block w-12 h-7 bg-slate-100 rounded animate-pulse"></span>
        </p>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <div class="flex items-center gap-2 mb-2">
            <div class="w-8 h-8 rounded-lg bg-violet-50 flex items-center justify-center">
                <i class="fas fa-handshake text-violet-500 text-sm"></i>
            </div>
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Acordos (30d)</p>
        </div>
        <p class="text-2xl font-bold text-violet-600 whitespace-nowrap" id="stat-acordos">
            <span class="inline-block w-12 h-7 bg-slate-100 rounded animate-pulse"></span>
        </p>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <div class="flex items-center gap-2 mb-2">
            <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center">
                <i class="fas fa-chart-line text-amber-500 text-sm"></i>
            </div>
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Taxa Conversão</p>
        </div>
        <p class="text-2xl font-bold text-amber-600 whitespace-nowrap" id="stat-conversao">
            <span class="inline-block w-12 h-7 bg-slate-100 rounded animate-pulse"></span>
        </p>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <div class="flex items-center gap-2 mb-2">
            <div class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center">
                <i class="fas fa-dollar-sign text-green-500 text-sm"></i>
            </div>
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Valor Acordado</p>
        </div>
        <p class="text-xl font-bold text-green-600 whitespace-nowrap" id="stat-valor">
            <span class="inline-block w-16 h-7 bg-slate-100 rounded animate-pulse"></span>
        </p>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <div class="flex items-center gap-2 mb-2">
            <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center">
                <i class="fas fa-users text-indigo-500 text-sm"></i>
            </div>
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Contatos na Fila</p>
        </div>
        <p class="text-2xl font-bold text-indigo-600 whitespace-nowrap" id="stat-fila">
            <span class="inline-block w-12 h-7 bg-slate-100 rounded animate-pulse"></span>
        </p>
    </div>
</div>

<!-- Campanhas Recentes -->
<div class="mb-8">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-slate-900">Campanhas Recentes</h2>
        <a href="/campanhas" class="text-sm text-brand-600 hover:text-brand-700 font-medium transition-colors">
            Ver todas <i class="fas fa-arrow-right ml-1 text-xs"></i>
        </a>
    </div>
    <div id="recentCampanhas" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-5 animate-pulse">
            <div class="h-4 bg-slate-100 rounded w-3/4 mb-3"></div>
            <div class="h-2 bg-slate-100 rounded w-full mb-3"></div>
            <div class="h-3 bg-slate-100 rounded w-1/2"></div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5 animate-pulse hidden md:block">
            <div class="h-4 bg-slate-100 rounded w-3/4 mb-3"></div>
            <div class="h-2 bg-slate-100 rounded w-full mb-3"></div>
            <div class="h-3 bg-slate-100 rounded w-1/2"></div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5 animate-pulse hidden md:block">
            <div class="h-4 bg-slate-100 rounded w-3/4 mb-3"></div>
            <div class="h-2 bg-slate-100 rounded w-full mb-3"></div>
            <div class="h-3 bg-slate-100 rounded w-1/2"></div>
        </div>
    </div>
</div>

<!-- Atividade Recente -->
<div>
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-slate-900">Atividade Recente</h2>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Data/Hora</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Campanha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Detalhes</th>
                    </tr>
                </thead>
                <tbody id="activityBody" class="bg-white divide-y divide-slate-100">
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-slate-400">
                            <i class="fas fa-spinner fa-spin text-lg"></i>
                            <p class="mt-2 text-sm">Carregando...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const STATUS_COLORS = {
        rascunho: { bg: 'bg-slate-100', text: 'text-slate-700', dot: 'bg-slate-400' },
        pronto: { bg: 'bg-blue-100', text: 'text-blue-700', dot: 'bg-blue-500' },
        ativo: { bg: 'bg-emerald-100', text: 'text-emerald-700', dot: 'bg-emerald-500' },
        pausado: { bg: 'bg-amber-100', text: 'text-amber-700', dot: 'bg-amber-500' },
        concluido: { bg: 'bg-violet-100', text: 'text-violet-700', dot: 'bg-violet-500' },
        cancelado: { bg: 'bg-red-100', text: 'text-red-700', dot: 'bg-red-500' },
    };

    document.addEventListener('DOMContentLoaded', function() {
        loadHomeStats();
        loadRecentCampanhas();
        loadRecentActivity();
    });

    async function loadHomeStats() {
        try {
            const response = await fetchWithAuth(`${API_BASE_URL}/dashboard/home-stats`);
            const data = await response.json();

            if (data.success && data.stats) {
                const s = data.stats;
                document.getElementById('stat-campanhas').textContent = s.campanhas_ativas;
                document.getElementById('stat-chamadas').textContent = s.chamadas_hoje;
                document.getElementById('stat-acordos').textContent = s.acordos_periodo;
                document.getElementById('stat-conversao').textContent = s.taxa_conversao + '%';
                document.getElementById('stat-valor').textContent = 'R$ ' + Number(s.valor_acordado).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                document.getElementById('stat-fila').textContent = s.contatos_fila;
            }
        } catch (error) {
            console.error('Erro ao carregar stats:', error);
            ['stat-campanhas', 'stat-chamadas', 'stat-acordos', 'stat-conversao', 'stat-valor', 'stat-fila'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.textContent = '-';
            });
        }
    }

    async function loadRecentCampanhas() {
        try {
            const response = await fetchWithAuth(`${API_BASE_URL}/filas_campanha`);
            const data = await response.json();
            const campanhas = (data.data || []).slice(0, 3);
            const container = document.getElementById('recentCampanhas');

            if (campanhas.length === 0) {
                container.innerHTML = `
                    <div class="col-span-3 bg-white rounded-xl border border-slate-200 p-8 text-center">
                        <i class="fas fa-bullhorn text-3xl text-slate-300"></i>
                        <p class="mt-2 text-sm text-slate-500">Nenhuma campanha ainda</p>
                        <a href="/campanha/criar" class="inline-flex items-center gap-2 mt-3 text-sm text-brand-600 hover:text-brand-700 font-medium">
                            <i class="fas fa-plus"></i> Criar primeira campanha
                        </a>
                    </div>
                `;
                return;
            }

            container.innerHTML = campanhas.map(c => {
                const s = STATUS_COLORS[c.status] || STATUS_COLORS.rascunho;
                const stats = c.stats || {};
                const progresso = c.progresso || 0;

                return `
                    <a href="/campanhas/${c.id}" class="bg-white rounded-xl border border-slate-200 p-5 hover:shadow-md hover:border-slate-300 transition-all duration-200 block">
                        <div class="flex items-start justify-between mb-3">
                            <p class="text-sm font-semibold text-slate-900 truncate">${escapeHtml(c.nome)}</p>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium ${s.bg} ${s.text} flex-shrink-0 ml-2">
                                <span class="w-1.5 h-1.5 rounded-full ${s.dot}"></span>
                                ${capitalize(c.status)}
                            </span>
                        </div>
                        <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden mb-3">
                            <div class="h-full bg-brand-500 rounded-full" style="width: ${Math.min(progresso, 100)}%"></div>
                        </div>
                        <div class="flex items-center gap-4 text-xs text-slate-500">
                            <span><span class="font-medium text-slate-700">${stats.total || 0}</span> contatos</span>
                            <span><span class="font-medium text-emerald-600">${stats.completados || 0}</span> OK</span>
                            <span><span class="font-medium text-slate-700">${Math.round(progresso)}%</span></span>
                        </div>
                    </a>
                `;
            }).join('');
        } catch (error) {
            console.error('Erro ao carregar campanhas recentes:', error);
            document.getElementById('recentCampanhas').innerHTML = `
                <div class="col-span-3 bg-white rounded-xl border border-slate-200 p-6 text-center text-slate-400">
                    <p class="text-sm">Erro ao carregar campanhas</p>
                </div>
            `;
        }
    }

    async function loadRecentActivity() {
        try {
            const response = await fetchWithAuth(`${API_BASE_URL}/atividades?limit=5`);
            const data = await response.json();
            const activities = data.data || [];
            const tbody = document.getElementById('activityBody');

            if (activities.length === 0) {
                tbody.innerHTML = `<tr><td colspan="5" class="px-6 py-8 text-center text-slate-400"><i class="fas fa-inbox text-xl"></i><p class="mt-2 text-sm">Nenhuma atividade recente</p></td></tr>`;
                return;
            }

            tbody.innerHTML = activities.map(item => {
                const badgeClass = item.status === 'success' ? 'bg-emerald-100 text-emerald-700'
                    : (item.status === 'error' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700');
                return `
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-3 text-sm text-slate-600 whitespace-nowrap">${new Date(item.created_at).toLocaleString('pt-BR')}</td>
                        <td class="px-6 py-3 text-sm text-slate-900 font-medium">${item.campanha_nome || 'N/A'}</td>
                        <td class="px-6 py-3 text-sm text-slate-600">${item.tipo || 'N/A'}</td>
                        <td class="px-6 py-3"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${badgeClass}">${item.status}</span></td>
                        <td class="px-6 py-3 text-sm text-slate-500">${item.descricao || '-'}</td>
                    </tr>
                `;
            }).join('');
        } catch (error) {
            console.error('Erro ao carregar atividades:', error);
            document.getElementById('activityBody').innerHTML = `<tr><td colspan="5" class="px-6 py-8 text-center text-slate-400"><p class="text-sm">Erro ao carregar atividades</p></td></tr>`;
        }
    }

    function capitalize(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
</script>
@endsection
