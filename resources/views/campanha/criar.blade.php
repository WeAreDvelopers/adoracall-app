@extends('layouts.app')

@section('title', 'Criar Campanha - AdoraCall')

@section('content')
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Criar Nova Campanha</h1>
        <p class="text-slate-500 text-sm mt-1">Selecione uma estrat&eacute;gia e configure os par&acirc;metros de discagem</p>
    </div>

    <div id="alertContainer" class="mb-4"></div>

    <form id="criarCampanhaForm" class="space-y-6">

        <!-- Informações Básicas -->
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="text-base font-semibold text-slate-900 mb-4">
                <i class="fas fa-info-circle text-brand-500 mr-2"></i>Informa&ccedil;&otilde;es B&aacute;sicas
            </h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="nome">Nome da Campanha <span class="text-red-500">*</span></label>
                    <input class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors" type="text" id="nome" name="nome" placeholder="Ex: Cobran&ccedil;a Atraso M&eacute;dio - Mar&ccedil;o 2026" required>
                    <p class="text-xs text-slate-400 mt-1">Nome identificador da campanha</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="descricao">Descri&ccedil;&atilde;o</label>
                    <textarea class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors" id="descricao" name="descricao" rows="3" placeholder="Descreva os objetivos, p&uacute;blico-alvo e detalhes desta campanha..."></textarea>
                </div>
            </div>
        </div>

        <!-- Seleção de Estratégia -->
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-slate-900">
                    <i class="fas fa-bullseye text-brand-500 mr-2"></i>Tipo de P&uacute;blico
                    <span class="text-xs font-normal text-slate-400 ml-1">(opcional)</span>
                </h3>
                <a href="/tipos-publico" class="text-xs text-brand-600 hover:text-brand-700 font-medium transition-colors">
                    <i class="fas fa-cog mr-1"></i>Gerenciar tipos
                </a>
            </div>

            <div id="tiposPublicoGrid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <div class="col-span-full flex items-center justify-center py-6 text-slate-400 text-sm">
                    <i class="fas fa-spinner fa-spin mr-2"></i> Carregando tipos...
                </div>
            </div>

            <div id="strategyInsight" class="hidden mt-4 bg-brand-50 border-l-4 border-brand-500 rounded-r-lg p-4">
                <p class="text-sm text-brand-800"><strong>Estrat&eacute;gia Selecionada:</strong></p>
                <p id="insightText" class="text-sm text-brand-700 mt-1"></p>
            </div>
        </div>

        <!-- Configuração de Discagem -->
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="text-base font-semibold text-slate-900 mb-4">
                <i class="fas fa-phone text-brand-500 mr-2"></i>Configura&ccedil;&atilde;o de Discagem
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="max_tentativas">M&aacute;x. de Tentativas <span class="text-red-500">*</span></label>
                    <input class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors" type="number" id="max_tentativas" name="max_tentativas" value="6" min="1" max="10" required>
                    <p class="text-xs text-slate-400 mt-1">Total de tentativas por contato</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="velocidade_contatos_hora">Contatos por Hora <span class="text-red-500">*</span></label>
                    <input class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors" type="number" id="velocidade_contatos_hora" name="velocidade_contatos_hora" value="40" min="5" max="500" required>
                    <p class="text-xs text-slate-400 mt-1">Velocidade de processamento</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="intervalo_retry">Intervalo Retry (min) <span class="text-red-500">*</span></label>
                    <input class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors" type="number" id="intervalo_retry" name="intervalo_retry" value="360" min="5" max="1440" required>
                    <p class="text-xs text-slate-400 mt-1">Tempo entre tentativas (6h = 360 min)</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="prioridade">Prioridade <span class="text-red-500">*</span></label>
                    <select class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors" id="prioridade" name="prioridade" required>
                        <option value="baixa">Baixa</option>
                        <option value="normal" selected>Normal</option>
                        <option value="alta">Alta</option>
                        <option value="urgente">Urgente</option>
                    </select>
                </div>
            </div>

            <div id="discagemInsight" class="hidden mt-4 bg-slate-50 border-l-4 border-slate-400 rounded-r-lg p-4">
                <p class="text-sm text-slate-700"><strong>Dura&ccedil;&atilde;o estimada:</strong></p>
                <p id="durationText" class="text-sm text-slate-600 mt-1"></p>
            </div>
        </div>

        <!-- Agendamento -->
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="text-base font-semibold text-slate-900 mb-4">
                <i class="fas fa-calendar-alt text-brand-500 mr-2"></i>Agendamento (Opcional)
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="data_inicio_agendado">Data/Hora de In&iacute;cio</label>
                    <input class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors" type="datetime-local" id="data_inicio_agendado" name="data_inicio_agendado">
                    <p class="text-xs text-slate-400 mt-1">Deixar vazio para iniciar agora</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="data_fim_agendado">Data/Hora de T&eacute;rmino</label>
                    <input class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors" type="datetime-local" id="data_fim_agendado" name="data_fim_agendado">
                    <p class="text-xs text-slate-400 mt-1">Deixar vazio para sem limite</p>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-3 justify-end">
            <button type="button" class="px-4 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-medium rounded-lg text-sm transition-colors" onclick="window.history.back()">
                <i class="fas fa-arrow-left mr-1"></i> Cancelar
            </button>
            <button type="submit" class="px-6 py-2.5 bg-brand-500 hover:bg-brand-600 text-white font-semibold rounded-lg text-sm transition-colors shadow-sm" id="btnSubmit">
                <i class="fas fa-check-circle mr-1"></i> Criar Campanha
            </button>
        </div>
    </form>

    @push('scripts')
        <script>
            // Fallback: tipos padrão caso a API não retorne nenhum
            const tiposPadrao = [
                { slug: 'atraso_leve', nome: 'Atraso Leve', subtitulo: '1 a 15 dias', descricao: 'Cliente ainda "quente", maior chance de sucesso.', cor: '#22c55e', icone: 'A', max_tentativas: 6, dias_estimados: 2, velocidade_contatos_hora: 50, intervalo_retry: 240, prioridade: 'alta', faixa_velocidade: '40-60', insight: 'Taxa de contato alta. Vale insistir mais com intervalos menores entre tentativas.' },
                { slug: 'atraso_medio', nome: 'Atraso M\u00e9dio', subtitulo: '16 a 60 dias', descricao: 'Objetivo de negocia\u00e7\u00e3o e acordo.', cor: '#f97316', icone: 'B', max_tentativas: 6, dias_estimados: 3, velocidade_contatos_hora: 35, intervalo_retry: 360, prioridade: 'normal', faixa_velocidade: '30-40', insight: 'Menos insist\u00eancia di\u00e1ria (2-3 tentativas/dia), mas cobertura ampla de hor\u00e1rios.' },
                { slug: 'atraso_alto', nome: 'Atraso Alto', subtitulo: '61 a 180 dias', descricao: '\u00daltima tentativa autom\u00e1tica. Abordagem conservadora.', cor: '#ef4444', icone: 'C', max_tentativas: 5, dias_estimados: 3, velocidade_contatos_hora: 25, intervalo_retry: 720, prioridade: 'normal', faixa_velocidade: '20-30', insight: 'Contato mais dif\u00edcil. Evitar insist\u00eancia excessiva para n\u00e3o gerar bloqueios.' },
                { slug: 'inadimplencia_critica', nome: 'Inadimpl\u00eancia Cr\u00edtica', subtitulo: 'Pr\u00e9-jur\u00eddico', descricao: 'Comunica\u00e7\u00e3o formal e conservadora.', cor: '#b91c1c', icone: 'D', max_tentativas: 3, dias_estimados: 3, velocidade_contatos_hora: 20, intervalo_retry: 1440, prioridade: 'baixa', faixa_velocidade: '20', insight: 'Abordagem altamente conservadora. Apenas 1 tentativa por dia. Risco legal elevado.' },
                { slug: 'leads_novos', nome: 'Leads / Confirma\u00e7\u00e3o', subtitulo: 'Novos contatos', descricao: 'Valida\u00e7\u00e3o de contato. Taxa alta de resposta.', cor: '#3b82f6', icone: 'E', max_tentativas: 4, dias_estimados: 2, velocidade_contatos_hora: 55, intervalo_retry: 360, prioridade: 'alta', faixa_velocidade: '50-60', insight: 'Novo contato = alta receptividade. Focar em valida\u00e7\u00e3o r\u00e1pida e confirma\u00e7\u00e3o.' },
            ];

            let tiposDisponiveis = [];
            let tipoSelecionado = null;

            // ========== CARREGAR TIPOS ==========

            async function carregarTiposPublico() {
                const grid = document.getElementById('tiposPublicoGrid');

                try {
                    const res = await fetchWithAuth(`${API_BASE_URL}/tipos-publico?ativo=1`);
                    const data = await res.json();
                    tiposDisponiveis = (data.tipos && data.tipos.length > 0) ? data.tipos : tiposPadrao;
                } catch (e) {
                    tiposDisponiveis = tiposPadrao;
                }

                renderizarTiposPublico();
            }

            function renderizarTiposPublico() {
                const grid = document.getElementById('tiposPublicoGrid');

                grid.innerHTML = tiposDisponiveis.map(tipo => {
                    const slug = tipo.slug;
                    return `
                        <label class="cursor-pointer">
                            <input type="radio" name="tipo_publico" value="${slug}" class="hidden peer" onchange="selecionarTipo('${slug}')">
                            <div id="card-${slug}" class="border-2 border-slate-200 rounded-xl p-4 transition-all peer-checked:border-brand-500 peer-checked:bg-brand-50/30 hover:border-slate-300 hover:shadow-sm">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 rounded-full text-white flex items-center justify-center font-bold text-sm" style="background-color: ${tipo.cor}">${tipo.icone || tipo.nome.charAt(0)}</div>
                                    <div>
                                        <p class="font-semibold text-slate-900 text-sm">${tipo.nome}</p>
                                        <p class="text-xs text-slate-500">${tipo.subtitulo || ''}</p>
                                    </div>
                                </div>
                                ${tipo.descricao ? `<p class="text-xs text-slate-500 mb-3">${tipo.descricao}</p>` : ''}
                                <div class="space-y-1.5">
                                    <div class="flex justify-between text-xs bg-slate-50 rounded px-2 py-1"><span class="text-slate-500">Tentativas:</span><span class="text-brand-600 font-semibold">${tipo.max_tentativas}</span></div>
                                    <div class="flex justify-between text-xs bg-slate-50 rounded px-2 py-1"><span class="text-slate-500">Dias:</span><span class="text-brand-600 font-semibold">${tipo.dias_estimados}</span></div>
                                    <div class="flex justify-between text-xs bg-slate-50 rounded px-2 py-1"><span class="text-slate-500">Por hora:</span><span class="text-brand-600 font-semibold">${tipo.faixa_velocidade || tipo.velocidade_contatos_hora}</span></div>
                                </div>
                            </div>
                        </label>`;
                }).join('');
            }

            // ========== SELEÇÃO ==========

            function selecionarTipo(slug) {
                const tipo = tiposDisponiveis.find(t => t.slug === slug);
                if (!tipo) return;

                tipoSelecionado = tipo;

                document.getElementById('max_tentativas').value = tipo.max_tentativas;
                document.getElementById('velocidade_contatos_hora').value = tipo.velocidade_contatos_hora;
                document.getElementById('intervalo_retry').value = tipo.intervalo_retry;
                document.getElementById('prioridade').value = tipo.prioridade;

                const insightBox = document.getElementById('strategyInsight');
                const insightText = document.getElementById('insightText');

                if (tipo.insight) {
                    insightText.innerHTML = `<strong>${tipo.nome}${tipo.subtitulo ? ' (' + tipo.subtitulo + ')' : ''}:</strong> ${tipo.insight}`;
                    insightBox.classList.remove('hidden');
                } else {
                    insightBox.classList.add('hidden');
                }

                atualizarDuracao();
            }

            // ========== DURAÇÃO ==========

            function atualizarDuracao() {
                const tentativas = parseInt(document.getElementById('max_tentativas').value);
                const velocidade = parseInt(document.getElementById('velocidade_contatos_hora').value);
                const intervalo = parseInt(document.getElementById('intervalo_retry').value);

                const durationBox = document.getElementById('discagemInsight');
                const durationText = document.getElementById('durationText');

                const horasParaProcessar = (tentativas / velocidade) * 24;
                const diasEstimados = Math.ceil(horasParaProcessar / 24);

                durationText.innerHTML = `Com <strong>${tentativas} tentativas</strong> a <strong>${velocidade} contatos/hora</strong>, estimado <strong>${diasEstimados} a ${diasEstimados + 1} dias</strong> para conclus\u00e3o. Intervalo entre tentativas: <strong>${intervalo} minutos</strong> (${(intervalo / 60).toFixed(1)} horas).`;
                durationBox.classList.remove('hidden');
            }

            // ========== SUBMIT ==========

            document.addEventListener('DOMContentLoaded', function() {
                carregarTiposPublico();

                document.getElementById('criarCampanhaForm').addEventListener('submit', async function(e) {
                    e.preventDefault();

                    const dataInicio = document.getElementById('data_inicio_agendado').value;
                    const dataFim = document.getElementById('data_fim_agendado').value;
                    if (dataInicio && dataFim && new Date(dataFim) <= new Date(dataInicio)) {
                        showAlert('A data de t\u00e9rmino deve ser posterior \u00e0 data de in\u00edcio.', 'error');
                        return;
                    }

                    await submitForm();
                });

                document.getElementById('max_tentativas').addEventListener('change', atualizarDuracao);
                document.getElementById('velocidade_contatos_hora').addEventListener('change', atualizarDuracao);
                document.getElementById('intervalo_retry').addEventListener('change', atualizarDuracao);
            });

            async function submitForm() {
                const btnSubmit = document.getElementById('btnSubmit');
                try {
                    btnSubmit.disabled = true;
                    btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Criando...';

                    const tipoPublico = document.querySelector('input[name="tipo_publico"]:checked');

                    const formData = {
                        nome: document.getElementById('nome').value,
                        descricao: document.getElementById('descricao').value,
                        prioridade: document.getElementById('prioridade').value,
                        max_tentativas: parseInt(document.getElementById('max_tentativas').value),
                        velocidade_contatos_hora: parseInt(document.getElementById('velocidade_contatos_hora').value),
                        intervalo_retry: parseInt(document.getElementById('intervalo_retry').value),
                        data_inicio_agendado: document.getElementById('data_inicio_agendado').value || null,
                        data_fim_agendado: document.getElementById('data_fim_agendado').value || null,
                        tipo_publico: tipoPublico ? tipoPublico.value : null,
                    };

                    const response = await fetchWithAuth(`${API_BASE_URL}/filas_campanha`, { method: 'POST', body: JSON.stringify(formData) });
                    const data = await response.json();

                    if (response.ok) {
                        showAlert('Campanha criada com sucesso!', 'success');
                        setTimeout(() => { window.location.href = '/campanha/status-importacoes'; }, 1500);
                    } else {
                        let errorMsg = data.message || 'Erro ao criar campanha';
                        if (data.errors) {
                            const fieldNames = {
                                nome: 'Nome', descricao: 'Descri\u00e7\u00e3o', tipo_publico: 'Tipo de P\u00fablico',
                                max_tentativas: 'M\u00e1x. Tentativas', velocidade_contatos_hora: 'Contatos/Hora',
                                intervalo_retry: 'Intervalo Retry', prioridade: 'Prioridade',
                                data_inicio_agendado: 'Data In\u00edcio', data_fim_agendado: 'Data T\u00e9rmino'
                            };
                            const errors = Object.entries(data.errors).map(([field, msgs]) => {
                                const name = fieldNames[field] || field;
                                return `<strong>${name}:</strong> ${Array.isArray(msgs) ? msgs[0] : msgs}`;
                            });
                            errorMsg = errors.join('<br>');
                        }
                        showAlert(errorMsg, 'error');
                        btnSubmit.disabled = false;
                        btnSubmit.innerHTML = '<i class="fas fa-check-circle mr-1"></i> Criar Campanha';
                    }
                } catch (error) {
                    showAlert('Erro ao processar requisi\u00e7\u00e3o: ' + error.message, 'error');
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = '<i class="fas fa-check-circle mr-1"></i> Criar Campanha';
                }
            }

            function showAlert(message, type) {
                const alertContainer = document.getElementById('alertContainer');
                const bgClass = type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-red-50 border-red-200 text-red-700';
                const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
                alertContainer.innerHTML = `<div class="p-3 ${bgClass} border rounded-lg text-sm flex items-center gap-2"><i class="fas fa-${icon}"></i><span>${message}</span></div>`;
            }
        </script>
    @endpush
@endsection
