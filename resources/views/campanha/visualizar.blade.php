@extends('layouts.app')

@section('title', 'Campanha - AdoraCall')

@section('content')
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-slate-500 mb-4">
        <a href="/" class="hover:text-brand-600 transition-colors">Home</a>
        <i class="fas fa-chevron-right text-[10px] text-slate-300"></i>
        <a href="/campanhas" class="hover:text-brand-600 transition-colors">Campanhas</a>
        <i class="fas fa-chevron-right text-[10px] text-slate-300"></i>
        <span id="breadcrumbName" class="text-slate-900 font-medium">Carregando...</span>
    </nav>

    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900" id="campanhaTitle">Carregando...</h1>
            <p class="text-slate-500 text-sm mt-1" id="campanhaDesc"></p>
        </div>
        <div class="flex items-center gap-2" id="headerActions"></div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 mb-6 bg-slate-100 rounded-lg p-1 w-fit">
        <button onclick="switchTab('resumo')" id="tabResumo"
            class="tab-btn px-4 py-2 rounded-md text-sm font-medium transition-colors bg-white text-slate-900 shadow-sm">
            <i class="fas fa-chart-bar mr-1.5"></i>Resumo
        </button>
        <button onclick="switchTab('contatos')" id="tabContatos"
            class="tab-btn px-4 py-2 rounded-md text-sm font-medium transition-colors text-slate-500 hover:text-slate-700">
            <i class="fas fa-users mr-1.5"></i>Contatos
        </button>
        <button onclick="switchTab('chamadas')" id="tabChamadas"
            class="tab-btn px-4 py-2 rounded-md text-sm font-medium transition-colors text-slate-500 hover:text-slate-700">
            <i class="fas fa-phone-alt mr-1.5"></i>Chamadas
        </button>
        <button onclick="switchTab('importar')" id="tabImportar"
            class="tab-btn px-4 py-2 rounded-md text-sm font-medium transition-colors text-slate-500 hover:text-slate-700">
            <i class="fas fa-upload mr-1.5"></i>Importar
        </button>
    </div>

    <!-- ==================== TAB: RESUMO ==================== -->
    <div id="tabContentResumo">
        <!-- Info Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6" id="infoCards">
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Status</p>
                <p class="text-lg font-bold mt-1" id="infoStatus">-</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Prioridade</p>
                <p class="text-lg font-bold text-slate-900 mt-1" id="infoPrioridade">-</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Taxa Sucesso</p>
                <p class="text-lg font-bold text-emerald-600 mt-1" id="infoTaxa">-</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Progresso</p>
                <p class="text-lg font-bold text-brand-600 mt-1" id="infoProgresso">-</p>
                <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden mt-2">
                    <div class="h-full bg-brand-500 rounded-full transition-all duration-500" id="progressBar" style="width: 0%"></div>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
                <p class="text-2xl font-bold text-slate-900" id="statTotal">0</p>
                <p class="text-xs text-slate-500 mt-1">Total</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
                <p class="text-2xl font-bold text-amber-500" id="statPendentes">0</p>
                <p class="text-xs text-slate-500 mt-1">Pendentes</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
                <p class="text-2xl font-bold text-blue-500" id="statProcessando">0</p>
                <p class="text-xs text-slate-500 mt-1">Processando</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
                <p class="text-2xl font-bold text-emerald-600" id="statCompletados">0</p>
                <p class="text-xs text-slate-500 mt-1">Completados</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
                <p class="text-2xl font-bold text-red-500" id="statFalhados">0</p>
                <p class="text-xs text-slate-500 mt-1">Falhados</p>
            </div>
        </div>

        <!-- Gráfico de Status -->
        <div class="bg-white rounded-xl border border-slate-200 p-5 mb-6 hidden" id="chartContainer">
            <h3 class="text-sm font-semibold text-slate-900 mb-4"><i class="fas fa-chart-pie text-slate-400 mr-2"></i>Distribuição da Fila</h3>
            <div class="flex justify-center">
                <div style="max-width: 280px; width: 100%;">
                    <canvas id="statsChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Detalhes da Campanha -->
        <div class="bg-white rounded-xl border border-slate-200 p-5" id="campanhaDetails">
            <h3 class="text-sm font-semibold text-slate-900 mb-3"><i class="fas fa-info-circle text-slate-400 mr-2"></i>Detalhes</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm" id="detailsGrid">
                <div><span class="text-slate-400">Carregando...</span></div>
            </div>
        </div>
    </div>

    <!-- ==================== TAB: CONTATOS ==================== -->
    <div id="tabContentContatos" class="hidden">
        <!-- Filtros -->
        <div class="bg-white rounded-xl border border-slate-200 p-4 mb-4">
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[180px]">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Buscar</label>
                    <input type="text" id="contatoSearch" placeholder="Nome, telefone ou CPF..."
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Status</label>
                    <select id="contatoStatus" class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                        <option value="">Todos</option>
                        <option value="pendente">Pendente</option>
                        <option value="processando">Processando</option>
                        <option value="completado">Completado</option>
                        <option value="falha">Falha</option>
                    </select>
                </div>
                <button onclick="filtrarContatos()" class="px-3 py-2 bg-brand-500 hover:bg-brand-600 text-white font-medium rounded-lg text-sm transition-colors">
                    <i class="fas fa-search"></i>
                </button>
                <button onclick="limparFiltrosContatos()" class="px-3 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-600 rounded-lg text-sm transition-colors">
                    <i class="fas fa-undo"></i>
                </button>
                <button onclick="abrirModalContato()" class="ml-auto px-3 py-2 bg-brand-500 hover:bg-brand-600 text-white font-medium rounded-lg text-sm transition-colors inline-flex items-center gap-2">
                    <i class="fas fa-plus"></i> Adicionar Contato
                </button>
            </div>
        </div>

        <!-- Tabela -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Nome</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Telefone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">CPF</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Valor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Tentativas</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Ultima Ligacao</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Acoes</th>
                        </tr>
                    </thead>
                    <tbody id="contatosBody" class="bg-white divide-y divide-slate-100">
                        <tr><td colspan="8" class="px-6 py-12 text-center text-slate-400"><i class="fas fa-spinner fa-spin text-lg"></i><p class="mt-2 text-sm">Carregando contatos...</p></td></tr>
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between">
                <span class="text-sm text-slate-500" id="contatosPagInfo">-</span>
                <div class="flex gap-2">
                    <button onclick="contatosPagina(-1)" class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-600 rounded-lg text-sm" id="contatosPrev">Anterior</button>
                    <button onclick="contatosPagina(1)" class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-600 rounded-lg text-sm" id="contatosNext">Proxima</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== TAB: CHAMADAS ==================== -->
    <div id="tabContentChamadas" class="hidden">
        <!-- Metricas -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total</p>
                <p class="text-2xl font-bold text-slate-900 mt-1" id="callsTotal">0</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <p class="text-xs font-medium text-emerald-600 uppercase tracking-wide">Bem-Sucedidas</p>
                <p class="text-2xl font-bold text-slate-900 mt-1" id="callsSuccess">0</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <p class="text-xs font-medium text-red-500 uppercase tracking-wide">Falhadas</p>
                <p class="text-2xl font-bold text-slate-900 mt-1" id="callsFailed">0</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <p class="text-xs font-medium text-blue-500 uppercase tracking-wide">Duracao Media</p>
                <p class="text-2xl font-bold text-slate-900 mt-1" id="callsAvgDuration">0s</p>
            </div>
        </div>

        <!-- Tabela de Chamadas -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Telefone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Cliente</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Duracao</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Sentimento</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Data</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Acoes</th>
                        </tr>
                    </thead>
                    <tbody id="chamadasBody" class="bg-white divide-y divide-slate-100">
                        <tr><td colspan="8" class="px-6 py-12 text-center text-slate-400"><i class="fas fa-spinner fa-spin text-lg"></i><p class="mt-2 text-sm">Carregando chamadas...</p></td></tr>
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between">
                <span class="text-sm text-slate-500" id="chamadasPagInfo">-</span>
                <div class="flex gap-2">
                    <button onclick="chamadasPagina(-1)" class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-600 rounded-lg text-sm" id="chamadasPrev">Anterior</button>
                    <button onclick="chamadasPagina(1)" class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-600 rounded-lg text-sm" id="chamadasNext">Proxima</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== TAB: IMPORTAR ==================== -->
    <div id="tabContentImportar" class="hidden">
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="text-sm font-semibold text-slate-900 mb-4"><i class="fas fa-upload text-slate-400 mr-2"></i>Importar Contatos para esta Campanha</h3>

            <div id="importAlertContainer" class="mb-4"></div>

            <form id="importFormTab" class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Arquivo CSV ou Excel <span class="text-red-500">*</span></label>
                    <input class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500" type="file" id="importFile" accept=".csv,.xlsx,.xls" required>
                    <p class="text-xs text-slate-400 mt-1">Aceita .csv ou .xlsx ate 10MB. Colunas obrigatorias: nome, telefone</p>
                </div>

                <!-- Preview -->
                <div id="importPreviewContainer" class="hidden">
                    <h4 class="text-sm font-medium text-slate-700 mb-2">Preview</h4>
                    <div class="overflow-x-auto rounded-lg border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50" id="importPreviewHead"></thead>
                            <tbody class="divide-y divide-slate-100" id="importPreviewBody"></tbody>
                        </table>
                    </div>
                </div>

                <div class="flex items-center gap-3 justify-end pt-4 border-t border-slate-100">
                    <button type="submit" class="px-4 py-2 bg-brand-500 hover:bg-brand-600 text-white font-medium rounded-lg text-sm transition-colors shadow-sm" id="importSubmitBtn">
                        <i class="fas fa-upload mr-1"></i> Importar Contatos
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- ==================== MODAL: ADICIONAR CONTATO ==================== -->
    <div id="modalAdicionarContato" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4 hidden">
        <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl">
            <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                <h2 class="text-lg font-bold text-slate-900"><i class="fas fa-user-plus text-slate-400 mr-2"></i>Adicionar Contato</h2>
                <button onclick="fecharModalContato()" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-colors text-xl">&times;</button>
            </div>
            <form id="formAdicionarContato" onsubmit="salvarContato(event)" class="p-6 space-y-4">
                <div id="alertAdicionarContato"></div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-slate-700 mb-1">Nome <span class="text-red-500">*</span></label>
                        <input type="text" id="contatoNome" required placeholder="Nome completo"
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-slate-700 mb-1">Telefone <span class="text-red-500">*</span></label>
                        <input type="text" id="contatoTelefone" required placeholder="(11) 99999-9999"
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">CPF</label>
                        <input type="text" id="contatoCpf" placeholder="000.000.000-00"
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Valor Débito</label>
                        <input type="number" id="contatoValor" placeholder="0.00" min="0" step="0.01"
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-slate-700 mb-1">Empresa Credora</label>
                        <input type="text" id="contatoEmpresaCredora" placeholder="Nome da empresa"
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Vencimento</label>
                        <input type="date" id="contatoVencimento"
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100">
                    <button type="button" onclick="fecharModalContato()"
                        class="px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-medium rounded-lg text-sm transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" id="btnSalvarContato"
                        class="px-4 py-2 bg-brand-500 hover:bg-brand-600 text-white font-medium rounded-lg text-sm transition-colors shadow-sm">
                        <i class="fas fa-plus mr-1"></i> Adicionar
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('head-scripts')
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@push('scripts')
<script>
    const campanhaId = '{{ $campanhaId }}';
</script>
<script src="{{ asset('js/campanha-detalhe.js') }}"></script>
@endpush
