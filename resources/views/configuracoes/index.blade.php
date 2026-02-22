@extends('layouts.app')

@section('title', 'Configurações - URA Dvelopers')

@section('content')
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">
            <i class="fas fa-cog text-brand-500 mr-2"></i>Configurações
        </h1>
        <p class="text-slate-500 text-sm mt-1">Gerencie o perfil da empresa e os termos enviados ao agente de voz</p>
    </div>

    <!-- Alert Container -->
    <div id="alertContainer" class="mb-4"></div>

    <!-- Loading State -->
    <div id="loadingState" class="bg-white rounded-xl border border-slate-200 p-12 text-center">
        <i class="fas fa-spinner fa-spin text-2xl text-brand-500"></i>
        <p class="mt-3 text-sm text-slate-500">Carregando configurações...</p>
    </div>

    <!-- Tabs -->
    <div id="configContent" class="hidden">
        <div class="flex gap-1 mb-6 bg-slate-100 rounded-lg p-1 w-fit">
            <button onclick="switchTab('perfil')" id="tabPerfil"
                class="tab-btn px-4 py-2 rounded-md text-sm font-medium transition-colors bg-white text-slate-900 shadow-sm">
                <i class="fas fa-building mr-1.5"></i>Perfil da Empresa
            </button>
            <button onclick="switchTab('retell')" id="tabRetell"
                class="tab-btn px-4 py-2 rounded-md text-sm font-medium transition-colors text-slate-500 hover:text-slate-700">
                <i class="fas fa-phone-alt mr-1.5"></i>Termos da Fila (Retell)
            </button>
            <button onclick="switchTab('integracoes')" id="tabIntegracoes"
                class="tab-btn px-4 py-2 rounded-md text-sm font-medium transition-colors text-slate-500 hover:text-slate-700">
                <i class="fas fa-plug mr-1.5"></i>Integrações
            </button>
        </div>

        <!-- Tab: Perfil da Empresa -->
        <form id="perfilForm" class="space-y-6">
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <h3 class="text-base font-semibold text-slate-900 mb-4">
                    <i class="fas fa-building text-brand-500 mr-2"></i>Dados da Empresa Credora
                </h3>
                <p class="text-xs text-slate-400 mb-4">Estas informações identificam sua empresa no sistema e nas ligações</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5" for="nome">
                            Nome da Empresa <span class="text-red-500">*</span>
                        </label>
                        <input class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors"
                               type="text" id="nome" name="nome" placeholder="Nome da empresa" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5" for="cnpj">CNPJ</label>
                        <input class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors"
                               type="text" id="cnpj" name="cnpj" placeholder="00.000.000/0000-00" maxlength="18"
                               oninput="formatCnpjInput(this)">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5" for="email">Email</label>
                        <input class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors"
                               type="email" id="email" name="email" placeholder="contato@empresa.com.br">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5" for="telefone">Telefone</label>
                        <input class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors"
                               type="text" id="telefone" name="telefone" placeholder="(11) 99999-9999" maxlength="15"
                               oninput="formatPhoneInput(this)">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5" for="logo_url">URL do Logo</label>
                        <input class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors"
                               type="url" id="logo_url" name="logo_url" placeholder="https://exemplo.com/logo.png">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5" for="endereco">Endereço</label>
                        <textarea class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors"
                                  id="endereco" name="endereco" rows="2" placeholder="Rua, número, bairro, cidade - UF, CEP"></textarea>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" id="btnSalvarPerfil" class="px-6 py-2.5 bg-brand-500 hover:bg-brand-600 text-white font-semibold rounded-lg text-sm transition-colors shadow-sm">
                    <i class="fas fa-save mr-1"></i> Salvar Perfil
                </button>
            </div>
        </form>

        <!-- Tab: Configurações Retell -->
        <form id="retellForm" class="space-y-6 hidden">
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <h3 class="text-base font-semibold text-slate-900 mb-4">
                    <i class="fas fa-robot text-brand-500 mr-2"></i>Nome da Credora
                </h3>
                <p class="text-xs text-slate-400 mb-4">Nome que o agente de voz usará para se identificar durante as ligações</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5" for="nome_credora">
                            Nome da Empresa Credora
                        </label>
                        <input class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors"
                               type="text" id="nome_credora" name="nome_credora" placeholder="Ex: Alfa Serviços Financeiros">
                        <p class="text-xs text-slate-400 mt-1">Se vazio, será usado o nome da empresa cadastrado no perfil</p>
                    </div>
                </div>
            </div>

            <!-- Regras de Desconto, Parcelamento e Preview - desabilitados por enquanto -->
            <input type="hidden" id="limite_valor_desconto_alto" value="500">
            <input type="hidden" id="percentual_desconto_alto" value="10">
            <input type="hidden" id="percentual_desconto_baixo" value="5">
            <input type="hidden" id="max_parcelas" value="3">
            <input type="hidden" id="valor_minimo_parcela" value="100">

            <div class="flex justify-end">
                <button type="submit" id="btnSalvarRetell" class="px-6 py-2.5 bg-brand-500 hover:bg-brand-600 text-white font-semibold rounded-lg text-sm transition-colors shadow-sm">
                    <i class="fas fa-save mr-1"></i> Salvar Configurações
                </button>
            </div>
        </form>

        <!-- Tab: Integrações -->
        <form id="integracoesForm" class="space-y-6 hidden">
            <!-- Card 1: Status Indicator -->
            <div id="statusIntegracao" class="bg-blue-50 rounded-xl border border-blue-200 p-4 flex items-center gap-3">
                <i class="fas fa-info-circle text-blue-500 text-lg"></i>
                <div>
                    <p class="text-sm font-medium text-blue-800" id="statusIntegracaoTexto">Verificando credenciais...</p>
                    <p class="text-xs text-blue-600 mt-0.5" id="statusIntegracaoDetalhe">Carregando informações de integração</p>
                </div>
            </div>

            <!-- Card 2: Retell AI -->
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <h3 class="text-base font-semibold text-slate-900 mb-4">
                    <i class="fas fa-robot text-brand-500 mr-2"></i>Retell AI
                </h3>
                <p class="text-xs text-slate-400 mb-4">Credenciais da sua conta Retell AI para o agente de voz</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5" for="integracao_retell_api_key">
                            API Key
                        </label>
                        <div class="relative">
                            <input class="w-full px-3 py-2 pr-10 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors"
                                   type="password" id="integracao_retell_api_key" placeholder="key_••••••••">
                            <button type="button" class="toggle-pwd absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 hover:text-slate-600" onclick="togglePasswordVisibility('integracao_retell_api_key')">
                                <i class="fas fa-eye text-sm"></i>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5" for="integracao_retell_agent_id">
                            Agent ID (Cobrança)
                        </label>
                        <input class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors"
                               type="text" id="integracao_retell_agent_id" placeholder="agent_••••••••">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5" for="integracao_retell_agent_id_sales">
                            Agent ID Vendas (opcional)
                        </label>
                        <input class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors"
                               type="text" id="integracao_retell_agent_id_sales" placeholder="agent_••••••••">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5" for="integracao_retell_webhook_secret">
                            Webhook Secret
                        </label>
                        <div class="relative">
                            <input class="w-full px-3 py-2 pr-10 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors"
                                   type="password" id="integracao_retell_webhook_secret" placeholder="whsec_••••••••">
                            <button type="button" class="toggle-pwd absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 hover:text-slate-600" onclick="togglePasswordVisibility('integracao_retell_webhook_secret')">
                                <i class="fas fa-eye text-sm"></i>
                            </button>
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5" for="integracao_retell_from_number">
                            Numero de Telefone (from_number)
                        </label>
                        <input class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors"
                               type="text" id="integracao_retell_from_number" placeholder="+5511999887766">
                        <p class="text-xs text-slate-400 mt-1">Numero importado na sua conta Retell (conectado ao Twilio via dashboard do Retell)</p>
                    </div>
                </div>
            </div>

            <!-- Card 4: Info Note -->
            <div class="bg-amber-50 rounded-xl border border-amber-200 p-6">
                <h3 class="text-base font-semibold text-amber-900 mb-3">
                    <i class="fas fa-lightbulb text-amber-500 mr-2"></i>Informação
                </h3>
                <p class="text-sm text-amber-700">
                    Campos deixados em branco utilizarão as credenciais globais configuradas no servidor (variáveis de ambiente).
                    Preencha apenas se sua empresa possui credenciais próprias do Retell AI. A integração com Twilio é feita diretamente no dashboard do Retell.
                </p>
            </div>

            <div class="flex justify-end">
                <button type="submit" id="btnSalvarIntegracoes" class="px-6 py-2.5 bg-brand-500 hover:bg-brand-600 text-white font-semibold rounded-lg text-sm transition-colors shadow-sm">
                    <i class="fas fa-save mr-1"></i> Salvar Integrações
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    let empresaData = null;

    // ========== TABS ==========
    function switchTab(tab) {
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('bg-white', 'text-slate-900', 'shadow-sm');
            btn.classList.add('text-slate-500', 'hover:text-slate-700');
        });
        const tabMap = { perfil: 'tabPerfil', retell: 'tabRetell', integracoes: 'tabIntegracoes' };
        const activeBtn = document.getElementById(tabMap[tab]);
        activeBtn.classList.add('bg-white', 'text-slate-900', 'shadow-sm');
        activeBtn.classList.remove('text-slate-500', 'hover:text-slate-700');

        document.getElementById('perfilForm').classList.toggle('hidden', tab !== 'perfil');
        document.getElementById('retellForm').classList.toggle('hidden', tab !== 'retell');
        document.getElementById('integracoesForm').classList.toggle('hidden', tab !== 'integracoes');
    }

    // ========== FORMATTERS ==========
    function formatCnpjInput(input) {
        let value = input.value.replace(/\D/g, '');
        if (value.length > 14) value = value.substring(0, 14);
        if (value.length > 12) value = value.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{0,2})/, '$1.$2.$3/$4-$5');
        else if (value.length > 8) value = value.replace(/(\d{2})(\d{3})(\d{3})(\d{0,4})/, '$1.$2.$3/$4');
        else if (value.length > 5) value = value.replace(/(\d{2})(\d{3})(\d{0,3})/, '$1.$2.$3');
        else if (value.length > 2) value = value.replace(/(\d{2})(\d{0,3})/, '$1.$2');
        input.value = value;
    }

    function formatPhoneInput(input) {
        let value = input.value.replace(/\D/g, '');
        if (value.length > 11) value = value.substring(0, 11);
        if (value.length > 6) value = value.replace(/(\d{2})(\d{4,5})(\d{0,4})/, '($1) $2-$3');
        else if (value.length > 2) value = value.replace(/(\d{2})(\d{0,5})/, '($1) $2');
        input.value = value;
    }

    function formatCnpjDisplay(cnpj) {
        if (!cnpj) return '';
        const d = cnpj.replace(/\D/g, '');
        if (d.length !== 14) return cnpj;
        return d.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
    }

    function formatPhoneDisplay(phone) {
        if (!phone) return '';
        const d = phone.replace(/\D/g, '');
        if (d.length === 11) return d.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
        if (d.length === 10) return d.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
        return phone;
    }

    // ========== TOGGLE PASSWORD ==========
    function togglePasswordVisibility(inputId) {
        const input = document.getElementById(inputId);
        const icon = input.parentElement.querySelector('.toggle-pwd i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }



    // ========== ALERTS ==========
    function showAlert(message, type) {
        const alertContainer = document.getElementById('alertContainer');
        const bgClass = type === 'success'
            ? 'bg-emerald-50 border-emerald-200 text-emerald-700'
            : 'bg-red-50 border-red-200 text-red-700';
        const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
        alertContainer.innerHTML = `
            <div class="p-3 ${bgClass} border rounded-lg text-sm flex items-center gap-2">
                <i class="fas fa-${icon}"></i>
                <span>${message}</span>
            </div>
        `;
        if (type === 'success') setTimeout(() => { alertContainer.innerHTML = ''; }, 4000);
    }

    // ========== LOAD ==========
    async function loadConfiguracoes() {
        try {
            const response = await fetchWithAuth(`${API_BASE_URL}/configuracoes`);
            const data = await response.json();

            if (response.ok) {
                empresaData = data.data?.empresa || data.empresa;
                populateForm(empresaData);
            } else {
                showAlert(data.message || 'Erro ao carregar configurações', 'error');
            }
        } catch (error) {
            console.error('Erro:', error);
            showAlert('Erro ao conectar com o servidor', 'error');
        }
    }

    function populateForm(empresa) {
        // Perfil
        document.getElementById('nome').value = empresa.nome || '';
        document.getElementById('cnpj').value = formatCnpjDisplay(empresa.cnpj);
        document.getElementById('email').value = empresa.email || '';
        document.getElementById('telefone').value = formatPhoneDisplay(empresa.telefone);
        document.getElementById('endereco').value = empresa.endereco || '';
        document.getElementById('logo_url').value = empresa.logo_url || '';

        // Configurações Retell
        const config = empresa.configuracoes || {};
        document.getElementById('nome_credora').value = config.nome_credora || '';
        document.getElementById('percentual_desconto_alto').value = config.percentual_desconto_alto ?? 10;
        document.getElementById('percentual_desconto_baixo').value = config.percentual_desconto_baixo ?? 5;
        document.getElementById('limite_valor_desconto_alto').value = config.limite_valor_desconto_alto ?? 500;
        document.getElementById('max_parcelas').value = config.max_parcelas ?? 3;
        document.getElementById('valor_minimo_parcela').value = config.valor_minimo_parcela ?? 100;

        // Integrações
        const integracoes = empresa.integracoes || {};
        document.getElementById('integracao_retell_api_key').value = integracoes.integracao_retell_api_key || '';
        document.getElementById('integracao_retell_agent_id').value = integracoes.integracao_retell_agent_id || '';
        document.getElementById('integracao_retell_agent_id_sales').value = integracoes.integracao_retell_agent_id_sales || '';
        document.getElementById('integracao_retell_webhook_secret').value = integracoes.integracao_retell_webhook_secret || '';
        document.getElementById('integracao_retell_from_number').value = integracoes.integracao_retell_from_number || '';

        updateStatusIntegracao(integracoes);

        // Show content, hide loading
        document.getElementById('loadingState').classList.add('hidden');
        document.getElementById('configContent').classList.remove('hidden');
    }

    function updateStatusIntegracao(integracoes) {
        const statusContainer = document.getElementById('statusIntegracao');
        const statusTexto = document.getElementById('statusIntegracaoTexto');
        const statusDetalhe = document.getElementById('statusIntegracaoDetalhe');

        const hasRetell = !!(integracoes.integracao_retell_api_key || integracoes.integracao_retell_agent_id);

        if (hasRetell) {
            statusContainer.className = 'bg-emerald-50 rounded-xl border border-emerald-200 p-4 flex items-center gap-3';
            statusContainer.querySelector('i').className = 'fas fa-check-circle text-emerald-500 text-lg';
            statusTexto.className = 'text-sm font-medium text-emerald-800';
            statusDetalhe.className = 'text-xs text-emerald-600 mt-0.5';
            statusTexto.textContent = 'Credenciais próprias configuradas';
            statusDetalhe.textContent = 'Usando credenciais da empresa para: Retell AI';
        } else {
            statusContainer.className = 'bg-blue-50 rounded-xl border border-blue-200 p-4 flex items-center gap-3';
            statusContainer.querySelector('i').className = 'fas fa-info-circle text-blue-500 text-lg';
            statusTexto.className = 'text-sm font-medium text-blue-800';
            statusDetalhe.className = 'text-xs text-blue-600 mt-0.5';
            statusTexto.textContent = 'Usando credenciais globais';
            statusDetalhe.textContent = 'Nenhuma credencial própria configurada. O sistema utilizará as variáveis de ambiente do servidor.';
        }
    }

    // ========== SAVE PERFIL ==========
    async function salvarPerfil(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSalvarPerfil');

        try {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Salvando...';

            const formData = {
                nome: document.getElementById('nome').value.trim(),
                cnpj: document.getElementById('cnpj').value.replace(/\D/g, '') || null,
                email: document.getElementById('email').value.trim() || null,
                telefone: document.getElementById('telefone').value.replace(/\D/g, '') || null,
                endereco: document.getElementById('endereco').value.trim() || null,
                logo_url: document.getElementById('logo_url').value.trim() || null,
            };

            if (!formData.nome) {
                showAlert('O nome da empresa é obrigatório.', 'error');
                resetBtn(btn, '<i class="fas fa-save mr-1"></i> Salvar Perfil');
                return;
            }

            const response = await fetchWithAuth(`${API_BASE_URL}/configuracoes`, {
                method: 'PUT',
                body: JSON.stringify(formData)
            });
            const data = await response.json();

            if (response.ok) {
                showAlert('Perfil atualizado com sucesso!', 'success');
                // Atualizar empresa no localStorage
                const empresaLocal = JSON.parse(localStorage.getItem('empresa') || '{}');
                empresaLocal.nome = formData.nome;
                localStorage.setItem('empresa', JSON.stringify(empresaLocal));
                if (typeof initializeHeader === 'function') initializeHeader();
            } else {
                showAlert(data.message || 'Erro ao salvar', 'error');
            }
        } catch (error) {
            showAlert('Erro ao processar: ' + error.message, 'error');
        } finally {
            resetBtn(btn, '<i class="fas fa-save mr-1"></i> Salvar Perfil');
        }
    }

    // ========== SAVE RETELL ==========
    async function salvarRetell(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSalvarRetell');

        try {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Salvando...';

            const formData = {
                configuracoes: {
                    nome_credora: document.getElementById('nome_credora').value.trim(),
                    percentual_desconto_alto: parseFloat(document.getElementById('percentual_desconto_alto').value) || 10,
                    percentual_desconto_baixo: parseFloat(document.getElementById('percentual_desconto_baixo').value) || 5,
                    limite_valor_desconto_alto: parseFloat(document.getElementById('limite_valor_desconto_alto').value) || 500,
                    max_parcelas: parseInt(document.getElementById('max_parcelas').value) || 3,
                    valor_minimo_parcela: parseFloat(document.getElementById('valor_minimo_parcela').value) || 100,
                }
            };

            const response = await fetchWithAuth(`${API_BASE_URL}/configuracoes`, {
                method: 'PUT',
                body: JSON.stringify(formData)
            });
            const data = await response.json();

            if (response.ok) {
                showAlert('Configurações salvas com sucesso!', 'success');
            } else {
                showAlert(data.message || 'Erro ao salvar', 'error');
            }
        } catch (error) {
            showAlert('Erro ao processar: ' + error.message, 'error');
        } finally {
            resetBtn(btn, '<i class="fas fa-save mr-1"></i> Salvar Configurações');
        }
    }

    // ========== SAVE INTEGRACOES ==========
    async function salvarIntegracoes(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSalvarIntegracoes');

        try {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Salvando...';

            const formData = {
                integracoes: {
                    integracao_retell_api_key: document.getElementById('integracao_retell_api_key').value,
                    integracao_retell_agent_id: document.getElementById('integracao_retell_agent_id').value,
                    integracao_retell_agent_id_sales: document.getElementById('integracao_retell_agent_id_sales').value,
                    integracao_retell_webhook_secret: document.getElementById('integracao_retell_webhook_secret').value,
                    integracao_retell_from_number: document.getElementById('integracao_retell_from_number').value,
                }
            };

            const response = await fetchWithAuth(`${API_BASE_URL}/configuracoes`, {
                method: 'PUT',
                body: JSON.stringify(formData)
            });
            const data = await response.json();

            if (response.ok) {
                showAlert('Integrações salvas com sucesso!', 'success');
                // Update local data and status indicator
                if (empresaData) {
                    empresaData.integracoes = formData.integracoes;
                }
                updateStatusIntegracao(formData.integracoes);
            } else {
                showAlert(data.message || 'Erro ao salvar integrações', 'error');
            }
        } catch (error) {
            showAlert('Erro ao processar: ' + error.message, 'error');
        } finally {
            resetBtn(btn, '<i class="fas fa-save mr-1"></i> Salvar Integrações');
        }
    }

    function resetBtn(btn, html) {
        btn.disabled = false;
        btn.innerHTML = html;
    }

    // ========== INIT ==========
    document.addEventListener('DOMContentLoaded', function() {
        loadConfiguracoes();

        document.getElementById('perfilForm').addEventListener('submit', salvarPerfil);
        document.getElementById('retellForm').addEventListener('submit', salvarRetell);
        document.getElementById('integracoesForm').addEventListener('submit', salvarIntegracoes);
    });
</script>
@endpush
