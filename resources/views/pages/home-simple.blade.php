@extends('layouts.app')

@section('title', 'Home - URA Dvelopers')

@section('content')

<!-- Page Header -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900">Bem-vindo ao Sistema de URA</h1>
    <p class="text-slate-500 mt-1">Selecione uma op&ccedil;&atilde;o para come&ccedil;ar</p>
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
        const user = JSON.parse(localStorage.getItem('user') || 'null');
        if (user) {
            // Already handled by header component
        }
    });
</script>
@endsection
