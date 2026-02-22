@extends('layouts.app')

@section('title', 'Criar Campanha - URA Dvelopers')

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
            <h3 class="text-base font-semibold text-slate-900 mb-4">
                <i class="fas fa-bullseye text-brand-500 mr-2"></i>Tipo de P&uacute;blico
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <!-- Público A -->
                <label class="cursor-pointer">
                    <input type="radio" name="tipo_publico" value="atraso_leve" class="hidden peer" onchange="atualizarEstrategia('atraso_leve')">
                    <div id="card-atraso_leve" class="border-2 border-slate-200 rounded-xl p-4 transition-all peer-checked:border-brand-500 peer-checked:bg-brand-50/30 hover:border-slate-300 hover:shadow-sm">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-full bg-green-500 text-white flex items-center justify-center font-bold text-sm">A</div>
                            <div>
                                <p class="font-semibold text-slate-900 text-sm">Atraso Leve</p>
                                <p class="text-xs text-slate-500">1 a 15 dias</p>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500 mb-3">Cliente ainda "quente", maior chance de sucesso.</p>
                        <div class="space-y-1.5">
                            <div class="flex justify-between text-xs bg-slate-50 rounded px-2 py-1"><span class="text-slate-500">Tentativas:</span><span class="text-brand-600 font-semibold">6</span></div>
                            <div class="flex justify-between text-xs bg-slate-50 rounded px-2 py-1"><span class="text-slate-500">Dias:</span><span class="text-brand-600 font-semibold">2</span></div>
                            <div class="flex justify-between text-xs bg-slate-50 rounded px-2 py-1"><span class="text-slate-500">Por hora:</span><span class="text-brand-600 font-semibold">40-60</span></div>
                        </div>
                    </div>
                </label>

                <!-- Público B -->
                <label class="cursor-pointer">
                    <input type="radio" name="tipo_publico" value="atraso_medio" class="hidden peer" onchange="atualizarEstrategia('atraso_medio')">
                    <div id="card-atraso_medio" class="border-2 border-slate-200 rounded-xl p-4 transition-all peer-checked:border-brand-500 peer-checked:bg-brand-50/30 hover:border-slate-300 hover:shadow-sm">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-full bg-orange-500 text-white flex items-center justify-center font-bold text-sm">B</div>
                            <div>
                                <p class="font-semibold text-slate-900 text-sm">Atraso M&eacute;dio</p>
                                <p class="text-xs text-slate-500">16 a 60 dias</p>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500 mb-3">Objetivo de negocia&ccedil;&atilde;o e acordo.</p>
                        <div class="space-y-1.5">
                            <div class="flex justify-between text-xs bg-slate-50 rounded px-2 py-1"><span class="text-slate-500">Tentativas:</span><span class="text-brand-600 font-semibold">6</span></div>
                            <div class="flex justify-between text-xs bg-slate-50 rounded px-2 py-1"><span class="text-slate-500">Dias:</span><span class="text-brand-600 font-semibold">3</span></div>
                            <div class="flex justify-between text-xs bg-slate-50 rounded px-2 py-1"><span class="text-slate-500">Por hora:</span><span class="text-brand-600 font-semibold">30-40</span></div>
                        </div>
                    </div>
                </label>

                <!-- Público C -->
                <label class="cursor-pointer">
                    <input type="radio" name="tipo_publico" value="atraso_alto" class="hidden peer" onchange="atualizarEstrategia('atraso_alto')">
                    <div id="card-atraso_alto" class="border-2 border-slate-200 rounded-xl p-4 transition-all peer-checked:border-brand-500 peer-checked:bg-brand-50/30 hover:border-slate-300 hover:shadow-sm">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-full bg-red-500 text-white flex items-center justify-center font-bold text-sm">C</div>
                            <div>
                                <p class="font-semibold text-slate-900 text-sm">Atraso Alto</p>
                                <p class="text-xs text-slate-500">61 a 180 dias</p>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500 mb-3">&Uacute;ltima tentativa autom&aacute;tica. Abordagem conservadora.</p>
                        <div class="space-y-1.5">
                            <div class="flex justify-between text-xs bg-slate-50 rounded px-2 py-1"><span class="text-slate-500">Tentativas:</span><span class="text-brand-600 font-semibold">5</span></div>
                            <div class="flex justify-between text-xs bg-slate-50 rounded px-2 py-1"><span class="text-slate-500">Dias:</span><span class="text-brand-600 font-semibold">3</span></div>
                            <div class="flex justify-between text-xs bg-slate-50 rounded px-2 py-1"><span class="text-slate-500">Por hora:</span><span class="text-brand-600 font-semibold">20-30</span></div>
                        </div>
                    </div>
                </label>

                <!-- Público D -->
                <label class="cursor-pointer">
                    <input type="radio" name="tipo_publico" value="inadimplencia_critica" class="hidden peer" onchange="atualizarEstrategia('inadimplencia_critica')">
                    <div id="card-inadimplencia_critica" class="border-2 border-slate-200 rounded-xl p-4 transition-all peer-checked:border-brand-500 peer-checked:bg-brand-50/30 hover:border-slate-300 hover:shadow-sm">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-full bg-red-700 text-white flex items-center justify-center font-bold text-sm">D</div>
                            <div>
                                <p class="font-semibold text-slate-900 text-sm">Inadimpl&ecirc;ncia Cr&iacute;tica</p>
                                <p class="text-xs text-slate-500">Pr&eacute;-jur&iacute;dico</p>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500 mb-3">Comunica&ccedil;&atilde;o formal e conservadora.</p>
                        <div class="space-y-1.5">
                            <div class="flex justify-between text-xs bg-slate-50 rounded px-2 py-1"><span class="text-slate-500">Tentativas:</span><span class="text-brand-600 font-semibold">3</span></div>
                            <div class="flex justify-between text-xs bg-slate-50 rounded px-2 py-1"><span class="text-slate-500">Dias:</span><span class="text-brand-600 font-semibold">3</span></div>
                            <div class="flex justify-between text-xs bg-slate-50 rounded px-2 py-1"><span class="text-slate-500">Por hora:</span><span class="text-brand-600 font-semibold">20</span></div>
                        </div>
                    </div>
                </label>

                <!-- Público E -->
                <label class="cursor-pointer">
                    <input type="radio" name="tipo_publico" value="leads_novos" class="hidden peer" onchange="atualizarEstrategia('leads_novos')">
                    <div id="card-leads_novos" class="border-2 border-slate-200 rounded-xl p-4 transition-all peer-checked:border-brand-500 peer-checked:bg-brand-50/30 hover:border-slate-300 hover:shadow-sm">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-full bg-blue-500 text-white flex items-center justify-center font-bold text-sm">E</div>
                            <div>
                                <p class="font-semibold text-slate-900 text-sm">Leads / Confirma&ccedil;&atilde;o</p>
                                <p class="text-xs text-slate-500">Novos contatos</p>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500 mb-3">Valida&ccedil;&atilde;o de contato. Taxa alta de resposta.</p>
                        <div class="space-y-1.5">
                            <div class="flex justify-between text-xs bg-slate-50 rounded px-2 py-1"><span class="text-slate-500">Tentativas:</span><span class="text-brand-600 font-semibold">4</span></div>
                            <div class="flex justify-between text-xs bg-slate-50 rounded px-2 py-1"><span class="text-slate-500">Dias:</span><span class="text-brand-600 font-semibold">2</span></div>
                            <div class="flex justify-between text-xs bg-slate-50 rounded px-2 py-1"><span class="text-slate-500">Por hora:</span><span class="text-brand-600 font-semibold">50-60</span></div>
                        </div>
                    </div>
                </label>
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
            const estrategias = {
                atraso_leve: { nome: 'Atraso Leve (1-15 dias)', max_tentativas: 6, dias: 2, velocidade_contatos_hora: 50, intervalo_retry: 240, prioridade: 'alta', insight: 'Taxa de contato alta. Vale insistir mais com intervalos menores entre tentativas.' },
                atraso_medio: { nome: 'Atraso M\u00e9dio (16-60 dias)', max_tentativas: 6, dias: 3, velocidade_contatos_hora: 35, intervalo_retry: 360, prioridade: 'normal', insight: 'Menos insist\u00eancia di\u00e1ria (2-3 tentativas/dia), mas cobertura ampla de hor\u00e1rios.' },
                atraso_alto: { nome: 'Atraso Alto (61-180 dias)', max_tentativas: 5, dias: 3, velocidade_contatos_hora: 25, intervalo_retry: 720, prioridade: 'normal', insight: 'Contato mais dif\u00edcil. Evitar insist\u00eancia excessiva para n\u00e3o gerar bloqueios.' },
                inadimplencia_critica: { nome: 'Inadimpl\u00eancia Cr\u00edtica (Pr\u00e9-jur\u00eddico)', max_tentativas: 3, dias: 3, velocidade_contatos_hora: 20, intervalo_retry: 1440, prioridade: 'baixa', insight: 'Abordagem altamente conservadora. Apenas 1 tentativa por dia. Risco legal elevado.' },
                leads_novos: { nome: 'Leads / Confirma\u00e7\u00e3o de Dados', max_tentativas: 4, dias: 2, velocidade_contatos_hora: 55, intervalo_retry: 360, prioridade: 'alta', insight: 'Novo contato = alta receptividade. Focar em valida\u00e7\u00e3o r\u00e1pida e confirma\u00e7\u00e3o.' }
            };

            function atualizarEstrategia(tipo) {
                const estrategia = estrategias[tipo];
                if (!estrategia) return;

                document.getElementById('max_tentativas').value = estrategia.max_tentativas;
                document.getElementById('velocidade_contatos_hora').value = estrategia.velocidade_contatos_hora;
                document.getElementById('intervalo_retry').value = estrategia.intervalo_retry;
                document.getElementById('prioridade').value = estrategia.prioridade;

                const insightBox = document.getElementById('strategyInsight');
                const insightText = document.getElementById('insightText');
                insightText.innerHTML = `<strong>${estrategia.nome}:</strong> ${estrategia.insight}`;
                insightBox.classList.remove('hidden');

                atualizarDuracao();
            }

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

            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('criarCampanhaForm').addEventListener('submit', async function(e) {
                    e.preventDefault();
                    const tipoPublico = document.querySelector('input[name="tipo_publico"]:checked');
                    if (!tipoPublico) { showAlert('Selecione um tipo de p\u00fablico', 'error'); return; }

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

                    const formData = {
                        nome: document.getElementById('nome').value,
                        descricao: document.getElementById('descricao').value,
                        prioridade: document.getElementById('prioridade').value,
                        max_tentativas: parseInt(document.getElementById('max_tentativas').value),
                        velocidade_contatos_hora: parseInt(document.getElementById('velocidade_contatos_hora').value),
                        intervalo_retry: parseInt(document.getElementById('intervalo_retry').value),
                        data_inicio_agendado: document.getElementById('data_inicio_agendado').value || null,
                        data_fim_agendado: document.getElementById('data_fim_agendado').value || null,
                        tipo_publico: document.querySelector('input[name="tipo_publico"]:checked').value,
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
