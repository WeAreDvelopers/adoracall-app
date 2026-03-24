<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

// ============================================
// ROTAS WEB (Views Blade - HTML)
// ============================================

// ========== AUTENTICAÇÃO ==========
$router->get('/login', ['as' => 'auth.login', 'uses' => 'AuthController@showLoginForm']);
$router->get('/register', ['as' => 'auth.register', 'uses' => 'AuthController@showRegisterForm']);

// ========== PROFILE ==========
$router->get('/profile', ['as' => 'profile.show', 'uses' => 'ProfileController@show']);
$router->get('/profile/edit', ['as' => 'profile.edit', 'uses' => 'ProfileController@edit']);
$router->put('/profile', ['as' => 'profile.update', 'uses' => 'ProfileController@update']);
$router->post('/logout', ['as' => 'auth.logout', 'uses' => 'AuthController@logout']);

// ========== TEST ROUTES ==========
$router->get('/test-blade', function () {
    return view('test-blade');
});

// ========== ROTAS PROTEGIDAS (autenticação via client-side com check-auth.js) ==========

// Home
$router->get('/', ['as' => 'home', 'uses' => 'ViewController@home']);

// Páginas principais
$router->get('/cobranca', ['as' => 'pages.cobranca', 'uses' => 'ViewController@cobranca']);
$router->get('/vendas', ['as' => 'pages.vendas', 'uses' => 'ViewController@vendas']);

// Dashboard
$router->get('/dashboard', ['as' => 'dashboard.index', 'uses' => 'ViewController@dashboardIndex']);
$router->get('/dashboard/cobranca', ['as' => 'dashboard.cobranca', 'uses' => 'ViewController@dashboardCobranca']);
$router->get('/dashboard/vendas', ['as' => 'dashboard.vendas', 'uses' => 'ViewController@dashboardVendas']);
$router->get('/dashboard/acordos', ['as' => 'dashboard.acordos', 'uses' => 'DashboardAcordosController@index']);

// Campanhas (novas rotas canônicas)
$router->get('/campanhas', ['as' => 'campanhas.index', 'uses' => 'ViewController@campanhasIndex']);
$router->get('/campanhas/{id}', ['as' => 'campanhas.show', 'uses' => 'ViewController@visualizarCampanha']);

// Operação
$router->get('/operacao/painel', ['as' => 'operacao.painel', 'uses' => 'ViewController@gerenciarFila']);
$router->get('/operacao/chamadas', ['as' => 'operacao.chamadas', 'uses' => 'ViewController@statusLigacoes']);

// Tipos de Público
$router->get('/tipos-publico', ['as' => 'tipos-publico.index', 'uses' => 'ViewController@tiposPublico']);

// Campanha (rotas legadas - mantidas para backward compatibility)
$router->group(['prefix' => 'campanha'], function () use ($router) {
    $router->get('/criar', ['as' => 'campanha.criar', 'uses' => 'ViewController@criarCampanha']);
    $router->get('/importacao', ['as' => 'campanha.importacao', 'uses' => 'ViewController@importacao']);
    $router->get('/status-importacoes', ['as' => 'campanha.status-importacoes', 'uses' => 'ViewController@statusImportacoes']);
    $router->get('/status-fila', ['as' => 'campanha.status-fila', 'uses' => 'ViewController@statusFila']);
    $router->get('/status-ligacoes', ['as' => 'campanha.status-ligacoes', 'uses' => 'ViewController@statusLigacoes']);
    $router->get('/gerenciar-fila', ['as' => 'campanha.gerenciar-fila', 'uses' => 'ViewController@gerenciarFila']);
    $router->get('/{id}/visualizar', ['as' => 'campanha.visualizar', 'uses' => 'ViewController@visualizarCampanha']);
});

// ========== CONTATOS IMPORTADOS ==========
$router->get('/contatos', ['as' => 'contatos.index', 'uses' => 'ContatoController@index']);
$router->get('/contatos/{id}', ['as' => 'contatos.show', 'uses' => 'ContatoController@show']);

// ========== CONFIGURAÇÕES DA EMPRESA ==========
$router->get('/configuracoes', ['as' => 'configuracoes.index', 'uses' => 'ConfiguracaoController@index']);

// ========== ADMIN - SUPER ADMIN ==========
$router->group(['prefix' => 'admin'], function () use ($router) {
    $router->get('/empresas', ['as' => 'admin.empresas', 'uses' => 'EmpresaController@indexView']);
    $router->get('/empresas/criar', ['as' => 'admin.empresas.criar', 'uses' => 'EmpresaController@criarView']);
    $router->get('/empresas/{id}/editar', ['as' => 'admin.empresas.editar', 'uses' => 'EmpresaController@editarView']);
    $router->get('/usuarios', ['as' => 'admin.usuarios', 'uses' => 'EmpresaController@usuariosView']);
});

// ============================================
// ROTAS API (JSON) - Prefixo API
// ============================================
$router->group(['prefix' => 'api'], function () use ($router) {

    // ========== AUTENTICAÇÃO API ==========
    $router->group(['prefix' => 'auth'], function () use ($router) {
        $router->post('/login', ['middleware' => 'throttle:5,1', 'uses' => 'AuthController@login']);
        $router->post('/register', ['middleware' => 'throttle:3,1', 'uses' => 'AuthController@register']);
        $router->post('/logout', ['middleware' => ['auth.jwt', 'throttle:30,1'], 'uses' => 'AuthController@logout']);
        $router->get('/me', ['middleware' => ['auth.jwt', 'throttle:60,1'], 'uses' => 'AuthController@me']);
    });

    // ========== URA - CHAMADAS ==========
    $router->group(['prefix' => 'ura'], function () use ($router) {

        // Rotas de chamada principal (protegidas com rate limiting)
        $router->post('/call/start', ['middleware' => ['auth.jwt', 'throttle:10,1'], 'uses' => 'CallController@startCall']);
        $router->get('/call/{callId}/info', ['middleware' => ['auth.jwt', 'throttle:30,1'], 'uses' => 'CallController@getCallInfo']);

        // Validação de segurança
        $router->post('/validate-security', 'SecurityController@validateSecurity');

        // Twilio webhooks (PÚBLICAS - não precisam de autenticação)
        $router->group(['prefix' => 'twilio'], function () use ($router) {
            $router->post('/voice', 'TwilioController@voice');
            $router->post('/status', 'TwilioController@status');
        });

        // ========== IVR PROGRAMÁTICA (Twilio TwiML) ==========
        // Webhooks públicos - Twilio chama de fora, sem autenticação
        // Fluxo: welcome → confirm-identity → verify-cpf → debt-info → fetch-debt → cash-response → extension-response → installment-response
        $router->group(['prefix' => 'ivr'], function () use ($router) {
            $router->post('/welcome', 'UraIvrController@welcome');
            $router->post('/confirm-identity', 'UraIvrController@confirmIdentity');
            $router->post('/confirm-identity-retry', 'UraIvrController@confirmIdentityRetry');
            $router->post('/verify-cpf', 'UraIvrController@verifyCpf');
            $router->post('/debt-info', 'UraIvrController@debtInfo');
            $router->post('/fetch-debt', 'UraIvrController@fetchDebt');
            $router->post('/cash-response', 'UraIvrController@cashResponse');
            $router->post('/extension-response', 'UraIvrController@extensionResponse');
            $router->post('/offer-installments', 'UraIvrController@offerInstallments');
            $router->post('/installment-response', 'UraIvrController@installmentResponse');
            $router->post('/process-deal', 'UraIvrController@processDeal');
            $router->post('/status', 'UraIvrController@status');
        });

        // ========== RETELL WEBHOOKS ==========
        // Webhook do Retell (PÚBLICO - Retell chama de fora, rate limit maior pois é confiável)
        $router->post('/webhook', ['middleware' => 'throttle:100,1', 'uses' => 'WebhookController@handleRetellWebhook']);
        $router->get('/webhook/test', ['middleware' => 'throttle:30,1', 'uses' => 'WebhookController@testWebhook']);
        $router->post('/webhook/simulate', ['middleware' => 'throttle:10,1', 'uses' => 'WebhookController@simulateWebhook']);

        // Listar webhooks (protegido)
        $router->get('/webhooks', ['middleware' => 'auth.jwt', 'uses' => 'WebhookController@listWebhooks']);

        // Histórico e estatísticas (protegidas)
        $router->group(['prefix' => 'history', 'middleware' => 'auth.jwt'], function () use ($router) {
            $router->post('/list', 'CallHistoryController@listRetellCalls');
            $router->get('/call/{callId}', 'CallHistoryController@getCallDetails');
            $router->get('/statistics', 'CallHistoryController@getStatistics');
        });

        // ========== AGENTE DE VOZ - CONSULTAS (para Retell AI) ==========
        // Essas rotas são PÚBLICAS pois serão chamadas pelo agente de voz do Retell
        $router->group(['prefix' => 'devedor'], function () use ($router) {
            // Consultar dívida/débito do cliente por CPF
            $router->get('/{cpf}', 'URADevedorController@consultarDevida');
        });

        $router->group(['prefix' => 'propostas'], function () use ($router) {
            // Consultar propostas de quitação disponíveis por CPF
            // Tool: tool-fetch-proposals
            $router->get('/{cpf}', 'URADevedorController@consultarPropostas');

            // Formalizar/aceitar proposta (COMPATÍVEL COM AGENTE ANTIGO)
            $router->post('/aceitar', 'URADevedorController@aceitarProposta');

            // Resumo da proposta aceita (COMPATÍVEL COM AGENTE ANTIGO)
            $router->get('/{cpf}/resumo', 'URADevedorController@resumoProposta');
        });

        // ========== AGENTE DE VOZ - FORMALIZAÇÃO DE ACORDOS ==========
        $router->group(['prefix' => 'acordos'], function () use ($router) {
            // Criação de acordo via POST (método legado - com dados no body)
            // Tool: tool-confirm-agreement
            $router->post('/', 'URADevedorController@criarAcordo');

            // Opções de pagamento disponíveis para um CPF
            // GET /api/ura/acordos/{cpf}/opcoes
            // Tool: tool-fetch-payment-options
            $router->get('/{cpf}/opcoes', 'URADevedorController@obterOpcoesAcordo');

            // Status/Polling de um acordo em processamento
            // GET /api/ura/acordos/{acordoId}/status
            // Tool: tool-check-agreement-status
            $router->get('/{acordoId}/status', 'URADevedorController@obterStatusAcordo');

            // Criação de acordo via GET (método moderno - com parâmetros na rota)
            // Rota: /api/ura/acordos/{tipoAcordo}/{cpf}
            // Exemplos:
            //   GET /api/ura/acordos/avista/12345678901
            //   GET /api/ura/acordos/3vezes/12345678901
            //   GET /api/ura/acordos/12vezes/12345678901
            //   GET /api/ura/acordos/36/12345678901
            //   GET /api/ura/acordos/48/12345678901
            //   GET /api/ura/acordos/60/12345678901
            // Tool: tool-create-agreement
            $router->get('/{tipoAcordo}/{cpf}', 'URADevedorController@criarAcordoGet');
        });

        // ========== AGENTE DE VOZ - ESCALONAMENTO ==========
        // Endpoint de escalonamento para supervisor (NOVO - para Retell)
        // Tool: tool-escalate-supervisor
        $router->post('/escalar', 'URADevedorController@escalarSupervisor');

        // Analytics (protegidas)
        $router->group(['prefix' => 'analytics', 'middleware' => 'auth.jwt'], function () use ($router) {
            $router->get('/general', 'AnalyticsController@getGeneralAnalytics');
            $router->get('/sales', 'AnalyticsController@getSalesAnalytics');
            $router->get('/collection', 'AnalyticsController@getCollectionAnalytics');

            // Exportação
            $router->group(['prefix' => 'export'], function () use ($router) {
                $router->get('/csv', 'ExportController@exportCSV');
                $router->get('/pdf', 'ExportController@exportPDF');
            });
        });

        // ========== VENDAS ==========
        $router->group(['prefix' => 'vendas', 'middleware' => 'auth.jwt'], function () use ($router) {
            $router->post('/call', 'SalesController@startSalesCall');
            $router->get('/lead/{callId}/info', 'SalesController@getLeadInfo');
            $router->post('/registrar-interesse', 'SalesController@registrarInteresse');
            $router->post('/registrar-qualificacao', 'SalesController@registrarQualificacao');
            $router->post('/registrar-proximos-passos', 'SalesController@registrarProximosPassos');
        });
    });

    // ========== DASHBOARD CONSOLIDADO (NOVO) ==========
    $router->group(['prefix' => 'dashboard', 'middleware' => 'auth.jwt'], function () use ($router) {
        // Teste simples
        $router->get('/test', 'DashboardAcordosController@testSimple');

        // KPIs da home page (Big Numbers)
        $router->get('/home-stats', 'DashboardAcordosController@getHomeStats');

        // Estatísticas resumidas para o dashboard
        $router->get('/stats', 'DashboardAcordosController@getStats');

        // Dados consolidados de ligações e acordos
        $router->get('/consolidated', 'DashboardAcordosController@getConsolidated');

        // Estatísticas de acordos
        $router->get('/agreements', 'DashboardAcordosController@getAgreementStats');

        // Timeline de eventos
        $router->get('/timeline', 'DashboardAcordosController@getTimeline');
    });

    // ========== ATIVIDADES RECENTES ==========
    $router->group(['prefix' => 'atividades', 'middleware' => 'auth.jwt'], function () use ($router) {
        $router->get('/', 'DashboardAcordosController@getAtividades');
    });

    // ========== INTENÇÕES ==========
    $router->group(['prefix' => 'intencoes', 'middleware' => 'auth.jwt'], function () use ($router) {
        $router->post('/processar', 'IntencaoController@processar');
        $router->post('/gerar-proposta-pagamento', 'IntencaoController@gerarPropostaPagamento');
        $router->post('/agendar-pagamento', 'IntencaoController@agendarPagamento');
        $router->post('/interesse-negociar', 'IntencaoController@interesseNegociar');
    });

    // ========== SISTEMA FLEXÍVEL DE SCRIPTS ==========
    $router->group(['prefix' => 'scripts', 'middleware' => 'auth.jwt'], function () use ($router) {
        $router->get('/', 'ScriptController@index');
        $router->post('/', 'ScriptController@store');
        $router->get('/{id}', 'ScriptController@show');
        $router->put('/{id}', 'ScriptController@update');
        $router->delete('/{id}', 'ScriptController@destroy');

        // Ações especiais
        $router->post('/{id}/clonar', 'ScriptController@clonar');
        $router->post('/{id}/publicar', 'ScriptController@publicar');

        // Intenções dos Scripts
        $router->get('/{id}/intencoes', 'ScriptController@intencoes');
        $router->post('/{id}/intencoes', 'ScriptController@adicionarIntencao');
        $router->put('/{id}/intencoes/{intencaoId}', 'ScriptController@atualizarIntencao');
        $router->delete('/{id}/intencoes/{intencaoId}', 'ScriptController@removerIntencao');
    });

    // ========== TIPOS DE PÚBLICO ==========
    $router->group(['prefix' => 'tipos-publico', 'middleware' => 'auth.jwt'], function () use ($router) {
        $router->get('/', 'TipoPublicoController@index');
        $router->post('/', 'TipoPublicoController@store');
        $router->put('/reordenar', 'TipoPublicoController@reordenar');
        $router->get('/{id}', 'TipoPublicoController@show');
        $router->put('/{id}', 'TipoPublicoController@update');
        $router->delete('/{id}', 'TipoPublicoController@destroy');
    });

    // ========== FILAS DE CAMPANHA ==========
    $router->group(['prefix' => 'filas_campanha', 'middleware' => 'auth.jwt'], function () use ($router) {
        $router->get('/', 'MailingController@index');
        $router->post('/', 'MailingController@store');
        $router->get('/fila-stats', 'MailingController@listarContatosNaFila');
        $router->get('/ligacoes/stats', 'MailingController@getAllLigacoesStats');
        $router->get('/{id}', 'MailingController@show');
        $router->put('/{id}', 'MailingController@update');
        $router->delete('/{id}', 'MailingController@destroy');

        // Importação e Gestão
        $router->post('/{id}/importar', 'MailingController@importarCSV');
        $router->post('/{id}/ativar', 'MailingController@ativar');
        $router->post('/{id}/pausar', 'MailingController@pausar');
        $router->post('/{id}/retomar', 'MailingController@retomar');
        $router->post('/{id}/cancelar', 'MailingController@cancelar');
        $router->post('/{id}/retry-falhas', 'MailingController@retryFalhas');
        $router->post('/{id}/reiniciar', 'MailingController@reiniciar');

        // Monitoramento de Importação
        $router->get('/{id}/import-logs', 'MailingController@getImportLogs');
        $router->get('/{id}/import-stats', 'MailingController@getImportStats');
        $router->get('/{id}/import-status', 'MailingController@getImportStatus');
        $router->get('/{id}/import-report', 'MailingController@getImportReport');
        $router->get('/{id}/export-import-logs', 'MailingController@exportImportLogs');
        $router->delete('/{id}/import-logs', 'MailingController@clearImportLogs');

        // Contatos da fila
        $router->get('/{id}/contatos', 'MailingController@contatosDoMailing');
        $router->post('/{id}/contatos', 'MailingController@adicionarContato');
        $router->get('/{id}/contatos/stats', 'MailingController@contatosStats');

        // Monitoramento de ligações
        $router->get('/{id}/ligacoes/stats', 'MailingController@getLigacoesStats');
        $router->get('/{id}/ligacoes', 'MailingController@getLigacoes');
    });

    // ========== CONTATOS ENRIQUECIDOS ==========
    $router->group(['prefix' => 'contatos'], function () use ($router) {
        // Esta rota DEVE vir antes das rotas com {id}
        $router->post('/lote/enriquecer', 'ContatoEnriquecidoController@enriquecerLote');

        // Rotas por ID do contato
        $router->get('/{id}/enriquecido', 'ContatoEnriquecidoController@obterEnriquecido');
        $router->post('/{id}/enriquecer', 'ContatoEnriquecidoController@enriquecerContato');
        $router->get('/{id}/divida', 'ContatoEnriquecidoController@obterDivida');
        $router->get('/{id}/propostas', 'ContatoEnriquecidoController@obterPropostas');
        $router->get('/{id}/instrucoes-ura', 'ContatoEnriquecidoController@obterInstrucoesURA');

        // Rotas por telefone
        $router->get('/telefone/{telefone}/enriquecido', 'ContatoEnriquecidoController@obterEnriquecidoPorTelefone');
    });

    // ========== CONTROLE GLOBAL DA FILA ==========
    $router->group(['prefix' => 'queue', 'middleware' => 'auth.jwt'], function () use ($router) {
        $router->get('/status', 'QueueController@queueStatus');
        $router->post('/start', 'QueueController@queueStart');
        $router->post('/pause', 'QueueController@queuePause');
        $router->post('/resume', 'QueueController@queueResume');
        $router->post('/stop', 'QueueController@queueStop');
    });

    // ========== FILAS (QUEUES) ==========
    $router->group(['prefix' => 'queues'], function () use ($router) {
        $router->get('/', 'QueueController@index');
        $router->get('/stats', 'QueueController@stats');
        $router->get('/dashboard', 'QueueController@dashboard');
        $router->get('/workers/status', 'QueueController@workersStatus');
        $router->delete('/cleanup', 'QueueController@cleanup');

        // Filas por Mailing
        $router->get('/{mailingId}', 'QueueController@show');
        $router->get('/{mailingId}/jobs', 'QueueController@jobs');
        $router->post('/{mailingId}/prioridade', 'QueueController@alterarPrioridade');
    });

    // ========== PROPOSTAS DE PAGAMENTO E SMS ==========
    $router->group(['prefix' => 'propostas-pagamento', 'middleware' => 'auth.jwt'], function () use ($router) {
        $router->get('/', 'PagamentoController@listar');
        $router->post('/', 'PagamentoController@criar');
        $router->get('/estatisticas', 'PagamentoController@estatisticas');
        $router->get('/uuid/{uuid}', 'PagamentoController@buscarPorUuid');
        $router->get('/{id}', 'PagamentoController@mostrar');

        // Ações de Propostas
        $router->post('/{id}/reenviar-sms', 'PagamentoController@reenviarSms');
        $router->post('/{id}/cancelar', 'PagamentoController@cancelar');
        $router->post('/processar-expiradas', 'PagamentoController@processarExpiradas');
    });

    // ========== WEBHOOKS ==========
    $router->group(['prefix' => 'webhooks'], function () use ($router) {
        $router->post('/twilio/sms-status', 'PagamentoController@webhookTwilioSmsStatus');
        $router->post('/pagamento/confirmacao', 'PagamentoController@webhookPagamentoConfirmacao');
    });

    // ========== CONFIGURAÇÕES DA EMPRESA API ==========
    $router->group(['prefix' => 'configuracoes', 'middleware' => 'auth.jwt'], function () use ($router) {
        $router->get('/', 'ConfiguracaoController@getConfiguracoes');
        $router->put('/', 'ConfiguracaoController@updateConfiguracoes');
        $router->get('/diagnostico', 'ConfiguracaoController@diagnostico');
    });

    // ========== ADMIN - SUPER ADMIN API ==========
    $router->group(['prefix' => 'admin', 'middleware' => ['auth.jwt', 'super_admin']], function () use ($router) {
        // Empresas
        $router->get('/empresas', 'EmpresaController@index');
        $router->post('/empresas', 'EmpresaController@store');
        $router->get('/empresas/{id}', 'EmpresaController@show');
        $router->put('/empresas/{id}', 'EmpresaController@update');
        $router->delete('/empresas/{id}', 'EmpresaController@destroy');

        // Usuários
        $router->get('/usuarios', 'EmpresaController@listarUsuarios');
        $router->put('/usuarios/{id}/empresa', 'EmpresaController@moverUsuario');
    });

    // ========== RETELL AI - FUNCTION CALLING ==========
    $router->group(['prefix' => 'retell'], function () use ($router) {
        $router->post('/get-propostas', 'RetellFunctionController@getPropostas');
        $router->post('/aceitar-proposta', 'RetellFunctionController@aceitarProposta');
        $router->post('/webhook', 'RetellFunctionController@webhookLigacao');
    });

    // ========== NEGOCIAÇÃO - API para Retell (consulta API Adora) ==========
    $router->group(['prefix' => 'negociacao'], function () use ($router) {
        $router->post('/buscar', 'NegociacaoController@buscar');
    });

    // ========== RESULTADOS DE IMPORTAÇÃO ==========
    $router->group(['prefix' => 'resultados', 'middleware' => 'auth.jwt'], function () use ($router) {
        $router->get('/', ['as' => 'resultados.index', 'uses' => 'ResultadosImportacaoController@index']);
        $router->get('/{mailingId}', ['as' => 'resultados.show', 'uses' => 'ResultadosImportacaoController@show']);
        $router->get('/{mailingId}/exportar', ['as' => 'resultados.exportar', 'uses' => 'ResultadosImportacaoController@exportar']);
    });

    // ========== RESULTADOS API ==========
    $router->group(['prefix' => 'api/resultados', 'middleware' => 'auth.jwt'], function () use ($router) {
        $router->get('/{mailingId}/stats', 'ResultadosImportacaoController@apiStats');
    });
});
