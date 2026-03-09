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
            <button onclick="switchTab('diagnostico')" id="tabDiagnostico"
                class="tab-btn px-4 py-2 rounded-md text-sm font-medium transition-colors text-slate-500 hover:text-slate-700">
                <i class="fas fa-stethoscope mr-1.5"></i>Diagnóstico
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

            <!-- Card: Configurações da Ligação -->
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <h3 class="text-base font-semibold text-slate-900 mb-4">
                    <i class="fas fa-phone-alt text-brand-500 mr-2"></i>Configurações da Ligação
                </h3>
                <p class="text-xs text-slate-400 mb-4">Como a atendente virtual se apresenta durante as ligações</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5" for="modo_ligacao">
                            Modo de Ligação
                        </label>
                        <select class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors bg-white"
                                id="modo_ligacao">
                            <option value="ivr">IVR (Twilio) — Fluxo programático</option>
                            <option value="retell">Retell AI — Agente inteligente</option>
                        </select>
                        <p class="text-xs text-slate-400 mt-1">IVR usa fluxo com menus e DTMF/voz. Retell usa IA conversacional.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5" for="nome_atendente">
                            Nome da Atendente Virtual
                        </label>
                        <input class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors"
                               type="text" id="nome_atendente" name="nome_atendente" placeholder="Ex: Angélica" maxlength="100">
                        <p class="text-xs text-slate-400 mt-1">Se vazio, será usado "Angélica"</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5" for="artigo_empresa">
                            Artigo da Empresa
                        </label>
                        <select class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors bg-white"
                                id="artigo_empresa">
                            <option value="a">A (feminino) — Ex: da Alfa Serviços</option>
                            <option value="o">O (masculino) — Ex: do Banco Inter</option>
                        </select>
                        <p class="text-xs text-slate-400 mt-1">Define se usa "da" ou "do" antes do nome da credora</p>
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
                        <p class="text-xs text-slate-400 mt-1">Se vazio, será usado o nome da empresa do perfil</p>
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

            <!-- Card 3: Twilio IVR - Voz -->
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <h3 class="text-base font-semibold text-slate-900 mb-4">
                    <i class="fas fa-microphone text-brand-500 mr-2"></i>Twilio IVR — Voz e Velocidade
                </h3>
                <p class="text-xs text-slate-400 mb-4">Define a voz e a velocidade da fala nas ligações automáticas via URA</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5" for="integracao_twilio_voice">
                            Voz (Text-to-Speech)
                        </label>
                        <select class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors bg-white"
                                id="integracao_twilio_voice">
                            <option value="">Padrão (Google Neural2-A)</option>
                            <optgroup label="Google Neural2 (Recomendadas)">
                                <option value="Google.pt-BR-Neural2-A">Neural2-A — Feminina (Recomendada)</option>
                                <option value="Google.pt-BR-Neural2-B">Neural2-B — Masculina</option>
                                <option value="Google.pt-BR-Neural2-C">Neural2-C — Feminina</option>
                            </optgroup>
                            <optgroup label="Google WaveNet">
                                <option value="Google.pt-BR-Wavenet-A">WaveNet-A — Feminina</option>
                                <option value="Google.pt-BR-Wavenet-B">WaveNet-B — Masculina</option>
                            </optgroup>
                            <optgroup label="Amazon Polly (Legado)">
                                <option value="Polly.Camila">Camila — Feminina Neural</option>
                                <option value="Polly.Vitoria">Vitória — Feminina Standard</option>
                                <option value="Polly.Ricardo">Ricardo — Masculino Standard</option>
                            </optgroup>
                        </select>
                        <p class="text-xs text-slate-400 mt-1">Vozes Google Neural2 oferecem qualidade superior e mais naturalidade</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5" for="integracao_twilio_speech_rate">
                            Velocidade da Fala
                        </label>
                        <select class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors bg-white"
                                id="integracao_twilio_speech_rate">
                            <option value="">Padrão (medium)</option>
                            <option value="x-slow">Muito Lenta</option>
                            <option value="slow">Lenta</option>
                            <option value="medium">Média</option>
                            <option value="fast">Rápida</option>
                            <option value="x-fast">Muito Rápida</option>
                            <option value="80%">80% — Um pouco mais lenta</option>
                            <option value="90%">90% — Levemente mais lenta</option>
                            <option value="110%">110% — Levemente mais rápida</option>
                            <option value="120%">120% — Um pouco mais rápida</option>
                        </select>
                        <p class="text-xs text-slate-400 mt-1">Utiliza SSML prosody — compatível com vozes Polly</p>
                    </div>
                </div>
            </div>

            <!-- Card 4: Inteligência Artificial -->
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <h3 class="text-base font-semibold text-slate-900 mb-4">
                    <i class="fas fa-brain text-purple-500 mr-2"></i>Inteligência Artificial (IVR)
                </h3>
                <p class="text-xs text-slate-400 mb-4">Ative a IA para gerar mensagens mais naturais durante as ligações de cobrança via IVR</p>

                <!-- Toggle IA -->
                <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg mb-4">
                    <div>
                        <p class="text-sm font-medium text-slate-700">Ativar IA nas ligações</p>
                        <p class="text-xs text-slate-400 mt-0.5">Quando ativada, a IA gera mensagens personalizadas. Se falhar, usa mensagem padrão automaticamente.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="use_ai" class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-purple-500/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-500"></div>
                    </label>
                </div>

                <!-- Campos condicionais (mostrar quando IA ativada) -->
                <div id="aiFieldsContainer" class="space-y-4 hidden">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5" for="ai_model">
                                Modelo de IA
                            </label>
                            <select class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-colors bg-white"
                                    id="ai_model">
                                <option value="gpt-4o">GPT-4o (Recomendado)</option>
                                <option value="gpt-4o-mini">GPT-4o Mini (Mais rapido)</option>
                                <option value="gpt-4-turbo">GPT-4 Turbo</option>
                                <option value="gpt-3.5-turbo">GPT-3.5 Turbo (Economico)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5" for="integracao_openai_api_key">
                                OpenAI API Key
                            </label>
                            <div class="relative">
                                <input class="w-full px-3 py-2 pr-10 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-colors"
                                       type="password" id="integracao_openai_api_key" placeholder="sk-••••••••">
                                <button type="button" class="toggle-pwd absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 hover:text-slate-600" onclick="togglePasswordVisibility('integracao_openai_api_key')">
                                    <i class="fas fa-eye text-sm"></i>
                                </button>
                            </div>
                            <p class="text-xs text-slate-400 mt-1">Se vazio, usa a chave global do servidor</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5" for="ai_prompt">
                            <i class="fas fa-scroll text-purple-400 mr-1"></i>Script / Prompt da IA
                        </label>
                        <textarea class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-colors font-mono"
                                  id="ai_prompt" rows="8" placeholder="Deixe vazio para usar o prompt padr&#227;o (tom amig&#225;vel e acolhedor). Personalize aqui o comportamento e estilo da Ang&#233;lica."></textarea>
                        <p class="text-xs text-slate-400 mt-1">Define a personalidade e regras da IA nas ligacoes. Se vazio, usa o prompt padrao amigavel.</p>
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

        <!-- Tab: Diagnóstico -->
        <div id="diagnosticoTab" class="space-y-6 hidden">
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">
                            <i class="fas fa-stethoscope text-brand-500 mr-2"></i>Diagnóstico do Sistema
                        </h3>
                        <p class="text-xs text-slate-400 mt-1">Visualize como o worker resolve as configurações de ligação da sua empresa</p>
                    </div>
                    <button onclick="loadDiagnostico()" id="btnDiagnostico"
                        class="px-4 py-2 bg-brand-500 hover:bg-brand-600 text-white font-semibold rounded-lg text-sm transition-colors shadow-sm">
                        <i class="fas fa-sync-alt mr-1"></i> Atualizar
                    </button>
                </div>

                <div id="diagnosticoLoading" class="text-center py-8 hidden">
                    <i class="fas fa-spinner fa-spin text-2xl text-brand-500"></i>
                    <p class="mt-2 text-sm text-slate-500">Consultando configurações e APIs externas...</p>
                </div>

                <div id="diagnosticoContent" class="hidden">
                    <!-- Modo de Ligação -->
                    <div class="mb-6">
                        <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                            <i class="fas fa-phone-alt text-slate-400"></i> Modo de Ligação
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="bg-slate-50 rounded-lg p-3">
                                <div class="text-xs text-slate-500 mb-1">Modo ativo</div>
                                <div id="diagModo" class="text-sm font-semibold"></div>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-3">
                                <div class="text-xs text-slate-500 mb-1">Origem da configuração</div>
                                <div id="diagModoOrigem" class="text-sm font-medium text-slate-700"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Retell -->
                    <div class="mb-6">
                        <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                            <i class="fas fa-robot text-slate-400"></i> Retell AI
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="bg-slate-50 rounded-lg p-3">
                                <div class="text-xs text-slate-500 mb-1">Agent ID</div>
                                <div id="diagAgentId" class="text-sm font-mono text-slate-700"></div>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-3">
                                <div class="text-xs text-slate-500 mb-1">Agent ID - Origem</div>
                                <div id="diagAgentIdOrigem" class="text-sm font-medium text-slate-700"></div>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-3">
                                <div class="text-xs text-slate-500 mb-1">API Key</div>
                                <div id="diagApiKey" class="text-sm font-medium text-slate-700"></div>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-3">
                                <div class="text-xs text-slate-500 mb-1">Número de origem</div>
                                <div id="diagFromNumber" class="text-sm font-mono text-slate-700"></div>
                            </div>
                        </div>

                        <!-- Agent Info (da API Retell) -->
                        <div id="diagAgentInfoContainer" class="mt-3 hidden">
                            <div class="text-xs font-semibold text-slate-500 mb-2 flex items-center gap-1">
                                <i class="fas fa-cloud"></i> Dados do Agente (via API Retell)
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3">
                                    <div class="text-xs text-emerald-600 mb-1">Nome do Agente</div>
                                    <div id="diagAgentName" class="text-sm font-semibold text-emerald-800"></div>
                                </div>
                                <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3">
                                    <div class="text-xs text-emerald-600 mb-1">Versão</div>
                                    <div id="diagAgentVersion" class="text-sm font-semibold text-emerald-800"></div>
                                </div>
                                <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3">
                                    <div class="text-xs text-emerald-600 mb-1">Voz</div>
                                    <div id="diagAgentVoice" class="text-sm font-semibold text-emerald-800"></div>
                                </div>
                                <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3">
                                    <div class="text-xs text-emerald-600 mb-1">Idioma</div>
                                    <div id="diagAgentLang" class="text-sm font-semibold text-emerald-800"></div>
                                </div>
                                <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3">
                                    <div class="text-xs text-emerald-600 mb-1">LLM ID</div>
                                    <div id="diagAgentLlm" class="text-sm font-mono text-emerald-800 text-xs break-all"></div>
                                </div>
                                <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3">
                                    <div class="text-xs text-emerald-600 mb-1">Última modificação</div>
                                    <div id="diagAgentModified" class="text-sm font-semibold text-emerald-800"></div>
                                </div>
                            </div>
                        </div>

                        <div id="diagRetellErro" class="mt-3 hidden">
                            <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                                <div class="text-xs text-red-600 mb-1"><i class="fas fa-exclamation-triangle mr-1"></i>Erro ao consultar Retell API</div>
                                <div id="diagRetellErroMsg" class="text-sm text-red-700"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Twilio (se IVR) -->
                    <div id="diagTwilioContainer" class="mb-6 hidden">
                        <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                            <i class="fas fa-phone text-slate-400"></i> Twilio (IVR)
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="bg-slate-50 rounded-lg p-3">
                                <div class="text-xs text-slate-500 mb-1">Número de origem</div>
                                <div id="diagTwilioFrom" class="text-sm font-mono text-slate-700"></div>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-3">
                                <div class="text-xs text-slate-500 mb-1">Voz</div>
                                <div id="diagTwilioVoice" class="text-sm font-medium text-slate-700"></div>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-3">
                                <div class="text-xs text-slate-500 mb-1">Credenciais</div>
                                <div id="diagTwilioCreds" class="text-sm font-medium text-slate-700"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Config Status -->
                    <div class="mb-6">
                        <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                            <i class="fas fa-database text-slate-400"></i> Status do Banco
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="bg-slate-50 rounded-lg p-3">
                                <div class="text-xs text-slate-500 mb-1">empresa_configuracoes</div>
                                <div id="diagConfigExiste" class="text-sm font-medium"></div>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-3">
                                <div class="text-xs text-slate-500 mb-1">empresa_integracoes</div>
                                <div id="diagIntegExiste" class="text-sm font-medium"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Env Fallbacks -->
                    <div>
                        <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                            <i class="fas fa-server text-slate-400"></i> Fallbacks do Servidor (.env)
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="bg-slate-50 rounded-lg p-3">
                                <div class="text-xs text-slate-500 mb-1">USE_IVR_MODE</div>
                                <div id="diagEnvIvr" class="text-sm font-mono text-slate-700"></div>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-3">
                                <div class="text-xs text-slate-500 mb-1">RETELL_AGENT_ID</div>
                                <div id="diagEnvAgentId" class="text-sm font-mono text-slate-700 break-all"></div>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-3">
                                <div class="text-xs text-slate-500 mb-1">RETELL_API_KEY</div>
                                <div id="diagEnvApiKey" class="text-sm font-medium text-slate-700"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="diagnosticoEmpty" class="text-center py-8">
                    <i class="fas fa-stethoscope text-3xl text-slate-300"></i>
                    <p class="mt-2 text-sm text-slate-500">Clique em "Atualizar" para carregar o diagnóstico</p>
                </div>
            </div>
        </div>
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
        const tabMap = { perfil: 'tabPerfil', retell: 'tabRetell', integracoes: 'tabIntegracoes', diagnostico: 'tabDiagnostico' };
        const activeBtn = document.getElementById(tabMap[tab]);
        activeBtn.classList.add('bg-white', 'text-slate-900', 'shadow-sm');
        activeBtn.classList.remove('text-slate-500', 'hover:text-slate-700');

        document.getElementById('perfilForm').classList.toggle('hidden', tab !== 'perfil');
        document.getElementById('retellForm').classList.toggle('hidden', tab !== 'retell');
        document.getElementById('integracoesForm').classList.toggle('hidden', tab !== 'integracoes');
        document.getElementById('diagnosticoTab').classList.toggle('hidden', tab !== 'diagnostico');

        if (tab === 'diagnostico') loadDiagnostico();
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
        document.getElementById('nome_atendente').value = config.nome_atendente || '';
        document.getElementById('artigo_empresa').value = config.artigo_empresa || 'a';
        document.getElementById('modo_ligacao').value = config.modo_ligacao || 'ivr';
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
        document.getElementById('integracao_openai_api_key').value = integracoes.integracao_openai_api_key || '';
        document.getElementById('integracao_twilio_voice').value = integracoes.integracao_twilio_voice || '';
        document.getElementById('integracao_twilio_speech_rate').value = integracoes.integracao_twilio_speech_rate || '';

        // IA
        const useAi = !!empresa.use_ai;
        document.getElementById('use_ai').checked = useAi;
        document.getElementById('ai_model').value = empresa.ai_model || 'gpt-4o';
        document.getElementById('ai_prompt').value = empresa.ai_prompt || '';
        toggleAiFields(useAi);

        updateStatusIntegracao(integracoes, useAi);

        // Show content, hide loading
        document.getElementById('loadingState').classList.add('hidden');
        document.getElementById('configContent').classList.remove('hidden');
    }

    function toggleAiFields(show) {
        const container = document.getElementById('aiFieldsContainer');
        container.classList.toggle('hidden', !show);
    }

    function updateStatusIntegracao(integracoes, useAi) {
        const statusContainer = document.getElementById('statusIntegracao');
        const statusTexto = document.getElementById('statusIntegracaoTexto');
        const statusDetalhe = document.getElementById('statusIntegracaoDetalhe');

        const hasRetell = !!(integracoes.integracao_retell_api_key || integracoes.integracao_retell_agent_id);
        const hasOpenAi = !!(integracoes.integracao_openai_api_key);

        const servicos = [];
        if (hasRetell) servicos.push('Retell AI');
        if (hasOpenAi) servicos.push('OpenAI');
        if (useAi) servicos.push('IA Ativa');

        if (servicos.length > 0) {
            statusContainer.className = 'bg-emerald-50 rounded-xl border border-emerald-200 p-4 flex items-center gap-3';
            statusContainer.querySelector('i').className = 'fas fa-check-circle text-emerald-500 text-lg';
            statusTexto.className = 'text-sm font-medium text-emerald-800';
            statusDetalhe.className = 'text-xs text-emerald-600 mt-0.5';
            statusTexto.textContent = 'Credenciais configuradas';
            statusDetalhe.textContent = 'Usando credenciais da empresa para: ' + servicos.join(', ');
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
                configuracoes: {
                    nome_atendente: document.getElementById('nome_atendente').value.trim() || null,
                    artigo_empresa: document.getElementById('artigo_empresa').value || 'a',
                    modo_ligacao: document.getElementById('modo_ligacao').value || 'ivr',
                },
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
                use_ai: document.getElementById('use_ai').checked,
                ai_model: document.getElementById('ai_model').value,
                ai_prompt: document.getElementById('ai_prompt').value || null,
                integracoes: {
                    integracao_retell_api_key: document.getElementById('integracao_retell_api_key').value,
                    integracao_retell_agent_id: document.getElementById('integracao_retell_agent_id').value,
                    integracao_retell_agent_id_sales: document.getElementById('integracao_retell_agent_id_sales').value,
                    integracao_retell_webhook_secret: document.getElementById('integracao_retell_webhook_secret').value,
                    integracao_retell_from_number: document.getElementById('integracao_retell_from_number').value,
                    integracao_openai_api_key: document.getElementById('integracao_openai_api_key').value,
                    integracao_twilio_voice: document.getElementById('integracao_twilio_voice').value,
                    integracao_twilio_speech_rate: document.getElementById('integracao_twilio_speech_rate').value,
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
                    empresaData.use_ai = formData.use_ai;
                    empresaData.ai_model = formData.ai_model;
                    empresaData.ai_prompt = formData.ai_prompt;
                }
                updateStatusIntegracao(formData.integracoes, formData.use_ai);
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

    // ========== DIAGNÓSTICO ==========
    async function loadDiagnostico() {
        const loading = document.getElementById('diagnosticoLoading');
        const content = document.getElementById('diagnosticoContent');
        const empty = document.getElementById('diagnosticoEmpty');
        const btn = document.getElementById('btnDiagnostico');

        loading.classList.remove('hidden');
        content.classList.add('hidden');
        empty.classList.add('hidden');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Consultando...';

        try {
            const response = await fetchWithAuth(`${API_BASE_URL}/configuracoes/diagnostico`);
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Erro ao carregar diagnóstico');
            }

            const d = data.data;
            populateDiagnostico(d);
            content.classList.remove('hidden');
        } catch (error) {
            console.error('[DIAGNOSTICO] Erro:', error);
            empty.classList.remove('hidden');
            empty.innerHTML = `
                <i class="fas fa-exclamation-triangle text-3xl text-red-300"></i>
                <p class="mt-2 text-sm text-red-500">${error.message}</p>
            `;
        } finally {
            loading.classList.add('hidden');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync-alt mr-1"></i> Atualizar';
        }
    }

    function populateDiagnostico(d) {
        // Modo de ligação
        const modo = d.modo_ligacao?.valor || 'desconhecido';
        const isRetell = modo === 'retell';
        document.getElementById('diagModo').innerHTML = isRetell
            ? '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-800"><i class="fas fa-robot"></i> Retell AI</span>'
            : '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800"><i class="fas fa-phone"></i> IVR (Twilio)</span>';
        document.getElementById('diagModoOrigem').textContent = d.modo_ligacao?.origem || '-';

        // Retell
        document.getElementById('diagAgentId').textContent = d.retell?.agent_id || 'Não configurado';
        document.getElementById('diagAgentIdOrigem').textContent = d.retell?.agent_id_origem || '-';
        document.getElementById('diagApiKey').innerHTML = d.retell?.has_api_key
            ? '<span class="text-emerald-600"><i class="fas fa-check-circle mr-1"></i>Configurada</span>'
            : '<span class="text-red-600"><i class="fas fa-times-circle mr-1"></i>Não encontrada</span>';
        document.getElementById('diagFromNumber').textContent = d.retell?.from_number || 'Não configurado';

        // Agent info
        const agentInfo = d.retell?.agent_info;
        const agentInfoContainer = document.getElementById('diagAgentInfoContainer');
        if (agentInfo) {
            agentInfoContainer.classList.remove('hidden');
            document.getElementById('diagAgentName').textContent = agentInfo.agent_name || '-';
            document.getElementById('diagAgentVersion').textContent = agentInfo.version ?? '-';
            document.getElementById('diagAgentVoice').textContent = agentInfo.voice_id || '-';
            document.getElementById('diagAgentLang').textContent = agentInfo.language || '-';
            document.getElementById('diagAgentLlm').textContent = agentInfo.llm_id || '-';
            if (agentInfo.last_modified) {
                const dt = new Date(agentInfo.last_modified);
                document.getElementById('diagAgentModified').textContent = dt.toLocaleString('pt-BR');
            } else {
                document.getElementById('diagAgentModified').textContent = '-';
            }
        } else {
            agentInfoContainer.classList.add('hidden');
        }

        // Retell erro
        const erroContainer = document.getElementById('diagRetellErro');
        if (d.retell?.erro) {
            erroContainer.classList.remove('hidden');
            document.getElementById('diagRetellErroMsg').textContent = d.retell.erro;
        } else {
            erroContainer.classList.add('hidden');
        }

        // Twilio
        const twilioContainer = document.getElementById('diagTwilioContainer');
        if (d.twilio) {
            twilioContainer.classList.remove('hidden');
            document.getElementById('diagTwilioFrom').textContent = d.twilio.from_number || '-';
            document.getElementById('diagTwilioVoice').textContent = d.twilio.voice || '-';
            const hasSid = d.twilio.has_sid;
            const hasToken = d.twilio.has_token;
            document.getElementById('diagTwilioCreds').innerHTML = (hasSid && hasToken)
                ? '<span class="text-emerald-600"><i class="fas fa-check-circle mr-1"></i>SID + Token OK</span>'
                : '<span class="text-red-600"><i class="fas fa-times-circle mr-1"></i>Faltando ' + (!hasSid ? 'SID ' : '') + (!hasToken ? 'Token' : '') + '</span>';
        } else {
            twilioContainer.classList.add('hidden');
        }

        // Config status
        const checkHtml = '<span class="text-emerald-600"><i class="fas fa-check-circle mr-1"></i>Existe</span>';
        const xHtml = '<span class="text-red-600"><i class="fas fa-times-circle mr-1"></i>Não existe</span>';
        document.getElementById('diagConfigExiste').innerHTML = d.config_existe?.empresa_configuracoes ? checkHtml : xHtml;
        document.getElementById('diagIntegExiste').innerHTML = d.config_existe?.empresa_integracoes ? checkHtml : xHtml;

        // Env fallbacks
        document.getElementById('diagEnvIvr').textContent = String(d.env_fallbacks?.USE_IVR_MODE ?? '-');
        document.getElementById('diagEnvAgentId').textContent = d.env_fallbacks?.RETELL_AGENT_ID || '-';
        document.getElementById('diagEnvApiKey').innerHTML = d.env_fallbacks?.has_RETELL_API_KEY
            ? '<span class="text-emerald-600"><i class="fas fa-check-circle mr-1"></i>Definida</span>'
            : '<span class="text-amber-600"><i class="fas fa-minus-circle mr-1"></i>Não definida</span>';
    }

    // ========== INIT ==========
    document.addEventListener('DOMContentLoaded', function() {
        loadConfiguracoes();

        document.getElementById('perfilForm').addEventListener('submit', salvarPerfil);
        document.getElementById('retellForm').addEventListener('submit', salvarRetell);
        document.getElementById('integracoesForm').addEventListener('submit', salvarIntegracoes);

        // Toggle IA fields visibility
        document.getElementById('use_ai').addEventListener('change', function() {
            toggleAiFields(this.checked);
        });
    });
</script>
@endpush
