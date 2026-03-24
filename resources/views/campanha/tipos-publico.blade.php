@extends('layouts.app')

@section('title', 'Tipos de Público - AdoraCall')

@section('content')
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Tipos de P&uacute;blico</h1>
            <p class="text-slate-500 text-sm mt-1">Gerencie as estrat&eacute;gias de discagem por tipo de p&uacute;blico</p>
        </div>
        <button onclick="abrirModalCriar()" class="px-4 py-2.5 bg-brand-500 hover:bg-brand-600 text-white font-semibold rounded-lg text-sm transition-colors shadow-sm">
            <i class="fas fa-plus mr-1.5"></i> Novo Tipo
        </button>
    </div>

    <div id="alertContainer" class="mb-4"></div>

    <!-- Lista de Tipos -->
    <div id="tiposContainer" class="space-y-4">
        <div class="flex items-center justify-center py-12 text-slate-400">
            <i class="fas fa-spinner fa-spin mr-2"></i> Carregando...
        </div>
    </div>

    <!-- Modal Criar/Editar -->
    <div id="modalTipo" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="fecharModal()"></div>
        <div class="absolute inset-y-0 right-0 w-full max-w-lg bg-white shadow-xl overflow-y-auto">
            <div class="sticky top-0 bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between z-10">
                <h2 id="modalTitle" class="text-lg font-bold text-slate-900">Novo Tipo de P&uacute;blico</h2>
                <button onclick="fecharModal()" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
                    <i class="fas fa-times text-slate-400"></i>
                </button>
            </div>

            <form id="formTipo" class="p-6 space-y-5">
                <input type="hidden" id="tipoId" value="">

                <!-- Aparência -->
                <div class="bg-slate-50 rounded-xl p-4 space-y-4">
                    <h3 class="text-sm font-semibold text-slate-700"><i class="fas fa-palette text-brand-500 mr-1.5"></i>Apar&ecirc;ncia</h3>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1" for="nome">Nome <span class="text-red-500">*</span></label>
                            <input class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500" type="text" id="nome" placeholder="Ex: Atraso Leve" required>
                        </div>

                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1" for="subtitulo">Subt&iacute;tulo</label>
                            <input class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500" type="text" id="subtitulo" placeholder="Ex: 1 a 15 dias">
                        </div>

                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1" for="descricao">Descri&ccedil;&atilde;o</label>
                            <input class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500" type="text" id="descricao" placeholder="Ex: Cliente ainda quente, maior chance de sucesso.">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1" for="icone">&Iacute;cone (letra)</label>
                            <input class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm text-center font-bold focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500" type="text" id="icone" maxlength="3" value="A" placeholder="A">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1" for="cor">Cor</label>
                            <div class="flex items-center gap-2">
                                <input class="w-10 h-10 rounded-lg border border-slate-300 cursor-pointer p-0.5" type="color" id="cor" value="#22c55e">
                                <input class="flex-1 px-3 py-2 border border-slate-300 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500" type="text" id="corTexto" value="#22c55e" maxlength="20">
                            </div>
                        </div>
                    </div>

                    <!-- Preview -->
                    <div class="pt-2">
                        <p class="text-xs text-slate-400 mb-2">Preview:</p>
                        <div id="previewCard" class="border-2 border-slate-200 rounded-xl p-4 max-w-xs">
                            <div class="flex items-center gap-3 mb-2">
                                <div id="previewBadge" class="w-10 h-10 rounded-full bg-green-500 text-white flex items-center justify-center font-bold text-sm">A</div>
                                <div>
                                    <p id="previewNome" class="font-semibold text-slate-900 text-sm">Novo Tipo</p>
                                    <p id="previewSubtitulo" class="text-xs text-slate-500">Subtítulo</p>
                                </div>
                            </div>
                            <p id="previewDescricao" class="text-xs text-slate-500">Descrição do tipo de público.</p>
                        </div>
                    </div>
                </div>

                <!-- Configurações de Discagem -->
                <div class="bg-slate-50 rounded-xl p-4 space-y-4">
                    <h3 class="text-sm font-semibold text-slate-700"><i class="fas fa-phone text-brand-500 mr-1.5"></i>Configura&ccedil;&atilde;o de Discagem</h3>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1" for="max_tentativas">M&aacute;x. Tentativas</label>
                            <input class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500" type="number" id="max_tentativas" value="6" min="1" max="20">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1" for="dias_estimados">Dias Estimados</label>
                            <input class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500" type="number" id="dias_estimados" value="2" min="1" max="30">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1" for="velocidade_contatos_hora">Contatos/Hora</label>
                            <input class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500" type="number" id="velocidade_contatos_hora" value="50" min="5" max="500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1" for="intervalo_retry">Intervalo Retry (min)</label>
                            <input class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500" type="number" id="intervalo_retry" value="240" min="5" max="1440">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1" for="prioridade">Prioridade</label>
                            <select class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500" id="prioridade">
                                <option value="baixa">Baixa</option>
                                <option value="normal" selected>Normal</option>
                                <option value="alta">Alta</option>
                                <option value="urgente">Urgente</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1" for="faixa_velocidade">Faixa Velocidade</label>
                            <input class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500" type="text" id="faixa_velocidade" placeholder="Ex: 40-60">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1" for="insight">Insight / Dica</label>
                        <textarea class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500" id="insight" rows="2" placeholder="Dica exibida ao selecionar este tipo na criação de campanha"></textarea>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-3 justify-end pt-2">
                    <button type="button" class="px-4 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-medium rounded-lg text-sm transition-colors" onclick="fecharModal()">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-2.5 bg-brand-500 hover:bg-brand-600 text-white font-semibold rounded-lg text-sm transition-colors shadow-sm" id="btnSalvar">
                        <i class="fas fa-check mr-1"></i> <span id="btnSalvarText">Criar</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Confirmar Exclusão -->
    <div id="modalExcluir" class="fixed inset-0 z-50 hidden flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="fecharModalExcluir()"></div>
        <div class="relative bg-white rounded-xl shadow-xl max-w-sm w-full mx-4 p-6">
            <div class="text-center">
                <div class="w-12 h-12 rounded-full bg-red-100 text-red-500 flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-trash-alt text-lg"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-1">Excluir tipo de p&uacute;blico?</h3>
                <p class="text-sm text-slate-500 mb-5">Esta a&ccedil;&atilde;o n&atilde;o pode ser desfeita. Campanhas que usam este tipo n&atilde;o ser&atilde;o afetadas.</p>
                <div class="flex gap-3">
                    <button onclick="fecharModalExcluir()" class="flex-1 px-4 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-medium rounded-lg text-sm transition-colors">Cancelar</button>
                    <button onclick="confirmarExclusao()" class="flex-1 px-4 py-2.5 bg-red-500 hover:bg-red-600 text-white font-semibold rounded-lg text-sm transition-colors" id="btnConfirmarExcluir">Excluir</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            let tipos = [];
            let excluirId = null;

            // ========== CRUD ==========

            async function carregarTipos() {
                try {
                    const res = await fetchWithAuth(`${API_BASE_URL}/tipos-publico`);
                    const data = await res.json();
                    tipos = data.tipos || [];
                    renderizarTipos();
                } catch (e) {
                    showAlert('Erro ao carregar tipos de p\u00fablico: ' + e.message, 'error');
                }
            }

            function renderizarTipos() {
                const container = document.getElementById('tiposContainer');

                if (tipos.length === 0) {
                    container.innerHTML = `
                        <div class="bg-white rounded-xl border border-slate-200 p-12 text-center">
                            <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-users text-2xl"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-slate-700 mb-1">Nenhum tipo de p\u00fablico cadastrado</h3>
                            <p class="text-sm text-slate-500 mb-4">Crie tipos de p\u00fablico para categorizar suas campanhas de discagem.</p>
                            <button onclick="abrirModalCriar()" class="px-4 py-2 bg-brand-500 hover:bg-brand-600 text-white font-medium rounded-lg text-sm transition-colors">
                                <i class="fas fa-plus mr-1"></i> Criar Primeiro Tipo
                            </button>
                        </div>`;
                    return;
                }

                container.innerHTML = `
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        ${tipos.map((t, i) => renderCard(t, i)).join('')}
                    </div>`;
            }

            function renderCard(tipo, index) {
                const prioridadeLabel = { baixa: 'Baixa', normal: 'Normal', alta: 'Alta', urgente: 'Urgente' };
                const ativoClass = tipo.ativo ? '' : 'opacity-50';

                return `
                    <div class="bg-white rounded-xl border border-slate-200 p-5 transition-all hover:shadow-md ${ativoClass}" data-id="${tipo.id}">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full text-white flex items-center justify-center font-bold text-sm flex-shrink-0" style="background-color: ${tipo.cor}">${tipo.icone || tipo.nome.charAt(0)}</div>
                                <div>
                                    <p class="font-semibold text-slate-900 text-sm">${tipo.nome}</p>
                                    <p class="text-xs text-slate-500">${tipo.subtitulo || ''}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1">
                                ${!tipo.ativo ? '<span class="text-[10px] bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded font-medium">Inativo</span>' : ''}
                                <button onclick="abrirModalEditar(${tipo.id})" class="p-1.5 hover:bg-slate-100 rounded-lg transition-colors text-slate-400 hover:text-slate-600" title="Editar">
                                    <i class="fas fa-pen text-xs"></i>
                                </button>
                                <button onclick="abrirModalExcluir(${tipo.id})" class="p-1.5 hover:bg-red-50 rounded-lg transition-colors text-slate-400 hover:text-red-500" title="Excluir">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </div>
                        </div>

                        ${tipo.descricao ? `<p class="text-xs text-slate-500 mb-3">${tipo.descricao}</p>` : ''}

                        <div class="space-y-1.5">
                            <div class="flex justify-between text-xs bg-slate-50 rounded px-2 py-1">
                                <span class="text-slate-500">Tentativas:</span>
                                <span class="text-brand-600 font-semibold">${tipo.max_tentativas}</span>
                            </div>
                            <div class="flex justify-between text-xs bg-slate-50 rounded px-2 py-1">
                                <span class="text-slate-500">Dias:</span>
                                <span class="text-brand-600 font-semibold">${tipo.dias_estimados}</span>
                            </div>
                            <div class="flex justify-between text-xs bg-slate-50 rounded px-2 py-1">
                                <span class="text-slate-500">Por hora:</span>
                                <span class="text-brand-600 font-semibold">${tipo.faixa_velocidade || tipo.velocidade_contatos_hora}</span>
                            </div>
                            <div class="flex justify-between text-xs bg-slate-50 rounded px-2 py-1">
                                <span class="text-slate-500">Prioridade:</span>
                                <span class="text-brand-600 font-semibold">${prioridadeLabel[tipo.prioridade] || tipo.prioridade}</span>
                            </div>
                        </div>
                    </div>`;
            }

            // ========== MODAL ==========

            function abrirModalCriar() {
                document.getElementById('tipoId').value = '';
                document.getElementById('modalTitle').textContent = 'Novo Tipo de P\u00fablico';
                document.getElementById('btnSalvarText').textContent = 'Criar';
                limparFormulario();
                document.getElementById('modalTipo').classList.remove('hidden');
            }

            function abrirModalEditar(id) {
                const tipo = tipos.find(t => t.id === id);
                if (!tipo) return;

                document.getElementById('tipoId').value = tipo.id;
                document.getElementById('modalTitle').textContent = 'Editar Tipo de P\u00fablico';
                document.getElementById('btnSalvarText').textContent = 'Salvar';

                document.getElementById('nome').value = tipo.nome || '';
                document.getElementById('subtitulo').value = tipo.subtitulo || '';
                document.getElementById('descricao').value = tipo.descricao || '';
                document.getElementById('icone').value = tipo.icone || '';
                document.getElementById('cor').value = tipo.cor || '#22c55e';
                document.getElementById('corTexto').value = tipo.cor || '#22c55e';
                document.getElementById('max_tentativas').value = tipo.max_tentativas || 6;
                document.getElementById('dias_estimados').value = tipo.dias_estimados || 2;
                document.getElementById('velocidade_contatos_hora').value = tipo.velocidade_contatos_hora || 50;
                document.getElementById('intervalo_retry').value = tipo.intervalo_retry || 240;
                document.getElementById('prioridade').value = tipo.prioridade || 'normal';
                document.getElementById('faixa_velocidade').value = tipo.faixa_velocidade || '';
                document.getElementById('insight').value = tipo.insight || '';

                atualizarPreview();
                document.getElementById('modalTipo').classList.remove('hidden');
            }

            function fecharModal() {
                document.getElementById('modalTipo').classList.add('hidden');
            }

            function limparFormulario() {
                document.getElementById('nome').value = '';
                document.getElementById('subtitulo').value = '';
                document.getElementById('descricao').value = '';
                document.getElementById('icone').value = 'A';
                document.getElementById('cor').value = '#22c55e';
                document.getElementById('corTexto').value = '#22c55e';
                document.getElementById('max_tentativas').value = 6;
                document.getElementById('dias_estimados').value = 2;
                document.getElementById('velocidade_contatos_hora').value = 50;
                document.getElementById('intervalo_retry').value = 240;
                document.getElementById('prioridade').value = 'normal';
                document.getElementById('faixa_velocidade').value = '';
                document.getElementById('insight').value = '';
                atualizarPreview();
            }

            // ========== PREVIEW ==========

            function atualizarPreview() {
                const nome = document.getElementById('nome').value || 'Novo Tipo';
                const subtitulo = document.getElementById('subtitulo').value || 'Subt\u00edtulo';
                const descricao = document.getElementById('descricao').value || 'Descri\u00e7\u00e3o do tipo de p\u00fablico.';
                const icone = document.getElementById('icone').value || nome.charAt(0);
                const cor = document.getElementById('cor').value;

                document.getElementById('previewNome').textContent = nome;
                document.getElementById('previewSubtitulo').textContent = subtitulo;
                document.getElementById('previewDescricao').textContent = descricao;
                document.getElementById('previewBadge').textContent = icone;
                document.getElementById('previewBadge').style.backgroundColor = cor;
            }

            // ========== EXCLUIR ==========

            function abrirModalExcluir(id) {
                excluirId = id;
                document.getElementById('modalExcluir').classList.remove('hidden');
            }

            function fecharModalExcluir() {
                excluirId = null;
                document.getElementById('modalExcluir').classList.add('hidden');
            }

            async function confirmarExclusao() {
                if (!excluirId) return;

                const btn = document.getElementById('btnConfirmarExcluir');
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Excluindo...';

                try {
                    const res = await fetchWithAuth(`${API_BASE_URL}/tipos-publico/${excluirId}`, { method: 'DELETE' });
                    if (res.ok) {
                        showAlert('Tipo de p\u00fablico removido com sucesso', 'success');
                        fecharModalExcluir();
                        await carregarTipos();
                    } else {
                        const data = await res.json();
                        showAlert(data.error || 'Erro ao excluir', 'error');
                    }
                } catch (e) {
                    showAlert('Erro: ' + e.message, 'error');
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = 'Excluir';
                }
            }

            // ========== SUBMIT ==========

            async function salvar(e) {
                e.preventDefault();

                const id = document.getElementById('tipoId').value;
                const isEdit = !!id;

                const formData = {
                    nome: document.getElementById('nome').value,
                    subtitulo: document.getElementById('subtitulo').value || null,
                    descricao: document.getElementById('descricao').value || null,
                    icone: document.getElementById('icone').value || null,
                    cor: document.getElementById('cor').value,
                    max_tentativas: parseInt(document.getElementById('max_tentativas').value),
                    dias_estimados: parseInt(document.getElementById('dias_estimados').value),
                    velocidade_contatos_hora: parseInt(document.getElementById('velocidade_contatos_hora').value),
                    intervalo_retry: parseInt(document.getElementById('intervalo_retry').value),
                    prioridade: document.getElementById('prioridade').value,
                    faixa_velocidade: document.getElementById('faixa_velocidade').value || null,
                    insight: document.getElementById('insight').value || null,
                };

                const btn = document.getElementById('btnSalvar');
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Salvando...';

                try {
                    const url = isEdit
                        ? `${API_BASE_URL}/tipos-publico/${id}`
                        : `${API_BASE_URL}/tipos-publico`;

                    const res = await fetchWithAuth(url, {
                        method: isEdit ? 'PUT' : 'POST',
                        body: JSON.stringify(formData),
                    });

                    const data = await res.json();

                    if (res.ok) {
                        showAlert(isEdit ? 'Tipo atualizado com sucesso' : 'Tipo criado com sucesso', 'success');
                        fecharModal();
                        await carregarTipos();
                    } else {
                        let errorMsg = data.message || 'Erro ao salvar';
                        if (data.errors) {
                            const errors = Object.entries(data.errors).map(([field, msgs]) => {
                                return `<strong>${field}:</strong> ${Array.isArray(msgs) ? msgs[0] : msgs}`;
                            });
                            errorMsg = errors.join('<br>');
                        }
                        showAlert(errorMsg, 'error');
                    }
                } catch (e) {
                    showAlert('Erro: ' + e.message, 'error');
                } finally {
                    btn.disabled = false;
                    const txt = document.getElementById('tipoId').value ? 'Salvar' : 'Criar';
                    btn.innerHTML = `<i class="fas fa-check mr-1"></i> <span id="btnSalvarText">${txt}</span>`;
                }
            }

            // ========== ALERT ==========

            function showAlert(message, type) {
                const alertContainer = document.getElementById('alertContainer');
                const bgClass = type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-red-50 border-red-200 text-red-700';
                const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
                alertContainer.innerHTML = `<div class="p-3 ${bgClass} border rounded-lg text-sm flex items-center gap-2"><i class="fas fa-${icon}"></i><span>${message}</span></div>`;
                if (type === 'success') setTimeout(() => alertContainer.innerHTML = '', 3000);
            }

            // ========== INIT ==========

            document.addEventListener('DOMContentLoaded', function() {
                carregarTipos();

                document.getElementById('formTipo').addEventListener('submit', salvar);

                // Live preview
                ['nome', 'subtitulo', 'descricao', 'icone'].forEach(id => {
                    document.getElementById(id).addEventListener('input', atualizarPreview);
                });

                // Sync color inputs
                document.getElementById('cor').addEventListener('input', function() {
                    document.getElementById('corTexto').value = this.value;
                    atualizarPreview();
                });
                document.getElementById('corTexto').addEventListener('input', function() {
                    if (/^#[0-9a-f]{6}$/i.test(this.value)) {
                        document.getElementById('cor').value = this.value;
                    }
                    atualizarPreview();
                });
            });
        </script>
    @endpush
@endsection
