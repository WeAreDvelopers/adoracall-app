@extends('layouts.app')

@section('title', 'Todas as Campanhas - URA Dvelopers')

@section('content')
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Todas as Campanhas</h1>
            <p class="text-slate-500 text-sm mt-1">Gerencie e acompanhe suas campanhas de ligações</p>
        </div>
        <a href="{{ route('campanha.criar') }}" class="inline-flex items-center gap-2 bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2.5 rounded-lg transition-colors shadow-sm">
            <i class="fas fa-plus"></i>
            <span>Nova Campanha</span>
        </a>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-xl border border-slate-200 p-4 mb-6">
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[200px]">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input type="text" id="searchInput" placeholder="Buscar por nome da campanha..."
                        class="w-full pl-9 pr-4 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                </div>
            </div>
            <select id="statusFilter" class="border border-slate-300 rounded-lg text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                <option value="">Todos os status</option>
                <option value="rascunho">Rascunho</option>
                <option value="pronto">Pronto</option>
                <option value="ativo">Ativo</option>
                <option value="pausado">Pausado</option>
                <option value="concluido">Concluído</option>
                <option value="cancelado">Cancelado</option>
            </select>
            <button onclick="loadCampanhas()" class="inline-flex items-center gap-2 px-3 py-2 text-sm text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors">
                <i class="fas fa-sync-alt"></i>
                <span class="hidden sm:inline">Atualizar</span>
            </button>
        </div>
    </div>

    <!-- Stats resumo -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6" id="statsGrid">
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total</p>
            <p class="text-2xl font-bold text-slate-900 mt-1" id="statTotal">-</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <p class="text-xs font-medium text-emerald-600 uppercase tracking-wide">Ativas</p>
            <p class="text-2xl font-bold text-slate-900 mt-1" id="statAtivas">-</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <p class="text-xs font-medium text-amber-500 uppercase tracking-wide">Pausadas</p>
            <p class="text-2xl font-bold text-slate-900 mt-1" id="statPausadas">-</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <p class="text-xs font-medium text-blue-500 uppercase tracking-wide">Concluídas</p>
            <p class="text-2xl font-bold text-slate-900 mt-1" id="statConcluidas">-</p>
        </div>
    </div>

    <!-- Loading -->
    <div id="loadingState" class="bg-white rounded-xl border border-slate-200 p-12 text-center">
        <i class="fas fa-spinner fa-spin text-2xl text-brand-500"></i>
        <p class="mt-3 text-sm text-slate-500">Carregando campanhas...</p>
    </div>

    <!-- Empty State -->
    <div id="emptyState" class="hidden bg-white rounded-xl border border-slate-200 p-12 text-center">
        <i class="fas fa-bullhorn text-4xl text-slate-300"></i>
        <p class="mt-3 text-base font-medium text-slate-600">Nenhuma campanha encontrada</p>
        <p class="mt-1 text-sm text-slate-400">Crie uma nova campanha para começar</p>
        <a href="{{ route('campanha.criar') }}" class="inline-flex items-center gap-2 mt-4 bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            <i class="fas fa-plus"></i> Nova Campanha
        </a>
    </div>

    <!-- Cards Grid -->
    <div id="campanhasGrid" class="hidden grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
    </div>

    <!-- Paginação -->
    <div id="paginationContainer" class="hidden flex items-center justify-between mt-6">
        <p class="text-sm text-slate-500" id="paginationInfo"></p>
        <div class="flex items-center gap-2">
            <button onclick="changePage(-1)" id="prevBtn" class="px-3 py-1.5 text-sm border border-slate-300 rounded-lg hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fas fa-chevron-left"></i>
            </button>
            <span class="text-sm text-slate-600" id="pageDisplay"></span>
            <button onclick="changePage(1)" id="nextBtn" class="px-3 py-1.5 text-sm border border-slate-300 rounded-lg hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
@endsection

@push('scripts')
<script src="{{ asset('js/campanhas-lista.js') }}"></script>
@endpush
