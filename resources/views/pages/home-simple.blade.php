@extends('layouts.app')

@section('title', 'Home - URA Dvelopers')

@section('content')

<!-- Page Header -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900">Bem-vindo ao Sistema de URA</h1>
    <p class="text-slate-500 mt-1">Selecione uma op&ccedil;&atilde;o para come&ccedil;ar</p>
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
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Taxa Convers&atilde;o</p>
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

<!-- Navigation Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">

    <a href="{{ route('campanha.criar') }}" class="group bg-white rounded-xl border border-slate-200 p-5 hover:shadow-md hover:border-brand-200 transition-all duration-200">
        <div class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center mb-3 group-hover:bg-brand-50 transition-colors">
            <i class="fas fa-bullhorn text-indigo-500 group-hover:text-brand-500 transition-colors"></i>
        </div>
        <h3 class="text-sm font-semibold text-slate-900 mb-1">Criar Campanha</h3>
        <p class="text-xs text-slate-500">Crie uma nova campanha de liga&ccedil;&otilde;es</p>
    </a>

    <a href="{{ route('campanha.importacao') }}" class="group bg-white rounded-xl border border-slate-200 p-5 hover:shadow-md hover:border-brand-200 transition-all duration-200">
        <div class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center mb-3 group-hover:bg-brand-50 transition-colors">
            <i class="fas fa-upload text-indigo-500 group-hover:text-brand-500 transition-colors"></i>
        </div>
        <h3 class="text-sm font-semibold text-slate-900 mb-1">Importar Contatos</h3>
        <p class="text-xs text-slate-500">Importe lista via CSV</p>
    </a>

    <a href="{{ route('campanha.status-importacoes') }}" class="group bg-white rounded-xl border border-slate-200 p-5 hover:shadow-md hover:border-brand-200 transition-all duration-200">
        <div class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center mb-3 group-hover:bg-brand-50 transition-colors">
            <i class="fas fa-file-import text-indigo-500 group-hover:text-brand-500 transition-colors"></i>
        </div>
        <h3 class="text-sm font-semibold text-slate-900 mb-1">Status Importa&ccedil;&otilde;es</h3>
        <p class="text-xs text-slate-500">Acompanhe importa&ccedil;&otilde;es</p>
    </a>

    <a href="{{ route('campanha.status-fila') }}" class="group bg-white rounded-xl border border-slate-200 p-5 hover:shadow-md hover:border-brand-200 transition-all duration-200">
        <div class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center mb-3 group-hover:bg-brand-50 transition-colors">
            <i class="fas fa-list-check text-indigo-500 group-hover:text-brand-500 transition-colors"></i>
        </div>
        <h3 class="text-sm font-semibold text-slate-900 mb-1">Fila de Chamadas</h3>
        <p class="text-xs text-slate-500">Monitore a fila</p>
    </a>

    <a href="{{ route('campanha.gerenciar-fila') }}" class="group bg-white rounded-xl border border-slate-200 p-5 hover:shadow-md hover:border-brand-200 transition-all duration-200">
        <div class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center mb-3 group-hover:bg-brand-50 transition-colors">
            <i class="fas fa-sliders-h text-indigo-500 group-hover:text-brand-500 transition-colors"></i>
        </div>
        <h3 class="text-sm font-semibold text-slate-900 mb-1">Controle de Fila</h3>
        <p class="text-xs text-slate-500">Inicie, pause ou pare</p>
    </a>

    <a href="{{ route('campanha.status-ligacoes') }}" class="group bg-white rounded-xl border border-slate-200 p-5 hover:shadow-md hover:border-brand-200 transition-all duration-200">
        <div class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center mb-3 group-hover:bg-brand-50 transition-colors">
            <i class="fas fa-phone text-indigo-500 group-hover:text-brand-500 transition-colors"></i>
        </div>
        <h3 class="text-sm font-semibold text-slate-900 mb-1">Hist&oacute;rico de Chamadas</h3>
        <p class="text-xs text-slate-500">Acompanhe em tempo real</p>
    </a>

    <a href="{{ route('dashboard.index') }}" class="group bg-white rounded-xl border border-slate-200 p-5 hover:shadow-md hover:border-brand-200 transition-all duration-200">
        <div class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center mb-3 group-hover:bg-brand-50 transition-colors">
            <i class="fas fa-chart-line text-indigo-500 group-hover:text-brand-500 transition-colors"></i>
        </div>
        <h3 class="text-sm font-semibold text-slate-900 mb-1">Dashboard Geral</h3>
        <p class="text-xs text-slate-500">M&eacute;tricas e estat&iacute;sticas</p>
    </a>

    <a href="/dashboard/acordos" class="group bg-white rounded-xl border border-slate-200 p-5 hover:shadow-md hover:border-brand-200 transition-all duration-200">
        <div class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center mb-3 group-hover:bg-brand-50 transition-colors">
            <i class="fas fa-chart-pie text-indigo-500 group-hover:text-brand-500 transition-colors"></i>
        </div>
        <h3 class="text-sm font-semibold text-slate-900 mb-1">Acordos &amp; Convers&atilde;o</h3>
        <p class="text-xs text-slate-500">M&eacute;tricas de acordos</p>
    </a>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        loadHomeStats();
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
            console.error('Erro ao carregar stats da home:', error);
            // Mostra 0 em caso de erro
            ['stat-campanhas', 'stat-chamadas', 'stat-acordos', 'stat-conversao', 'stat-valor', 'stat-fila'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.textContent = '-';
            });
        }
    }
</script>
@endsection
