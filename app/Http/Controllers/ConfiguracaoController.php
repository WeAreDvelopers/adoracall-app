<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\EmpresaConfiguracao;
use App\Models\EmpresaIntegracao;
use App\Services\ApiResponseService;
use App\Services\IntegracaoService;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ConfiguracaoController extends Controller
{
    // ========== VIEW ==========

    public function index()
    {
        return view('configuracoes.index');
    }

    // ========== API ==========

    public function getConfiguracoes(Request $request)
    {
        if (!app()->bound('empresa_id')) {
            return ApiResponseService::forbidden('Empresa não identificada');
        }

        $empresa = Empresa::withoutGlobalScopes()->find(app('empresa_id'));

        if (!$empresa) {
            return ApiResponseService::notFound('Empresa não encontrada');
        }

        $configuracao = EmpresaConfiguracao::getForEmpresa($empresa->id);
        $integracao = $empresa->integracao;
        $integracoesMascaradas = IntegracaoService::maskCredentials($integracao);

        return ApiResponseService::success([
            'empresa' => [
                'id'             => $empresa->id,
                'nome'           => $empresa->nome,
                'cnpj'           => $empresa->cnpj,
                'email'          => $empresa->email,
                'telefone'       => $empresa->telefone,
                'endereco'       => $empresa->endereco,
                'logo_url'       => $empresa->logo_url,
                'use_ai'         => (bool) $empresa->use_ai,
                'ai_model'       => $empresa->ai_model,
                'ai_prompt'      => $empresa->ai_prompt,
                'configuracoes'  => [
                    'nome_credora'               => $configuracao->nome_credora,
                    'nome_atendente'             => $configuracao->nome_atendente,
                    'artigo_empresa'             => $configuracao->artigo_empresa,
                    'modo_ligacao'               => $configuracao->modo_ligacao ?? 'ivr',
                    'percentual_desconto_alto'   => $configuracao->percentual_desconto_alto,
                    'percentual_desconto_baixo'  => $configuracao->percentual_desconto_baixo,
                    'limite_valor_desconto_alto'  => $configuracao->limite_valor_desconto_alto,
                    'max_parcelas'               => $configuracao->max_parcelas,
                    'valor_minimo_parcela'       => $configuracao->valor_minimo_parcela,
                ],
                'integracoes'    => $integracoesMascaradas,
                'has_own_credentials' => IntegracaoService::hasOwnCredentials($empresa->id),
            ],
        ]);
    }

    public function updateConfiguracoes(Request $request)
    {
        if (!app()->bound('empresa_id')) {
            return ApiResponseService::forbidden('Empresa não identificada');
        }

        $empresa = Empresa::withoutGlobalScopes()->find(app('empresa_id'));

        if (!$empresa) {
            return ApiResponseService::notFound('Empresa não encontrada');
        }

        $validator = Validator::make($request->all(), [
            // Perfil da empresa
            'nome'           => 'sometimes|string|max:255',
            'cnpj'           => 'nullable|string|max:18',
            'email'          => 'nullable|email|max:255',
            'telefone'       => 'nullable|string|max:20',
            'endereco'       => 'nullable|string|max:500',
            'logo_url'       => 'nullable|url|max:500',

            // Configurações (termos da fila)
            'configuracoes'                              => 'sometimes|array',
            'configuracoes.nome_credora'                 => 'nullable|string|max:255',
            'configuracoes.nome_atendente'               => 'nullable|string|max:100',
            'configuracoes.artigo_empresa'               => 'nullable|string|in:a,o',
            'configuracoes.modo_ligacao'                 => 'nullable|string|in:ivr,retell',
            'configuracoes.percentual_desconto_alto'     => 'nullable|numeric|min:0|max:100',
            'configuracoes.percentual_desconto_baixo'    => 'nullable|numeric|min:0|max:100',
            'configuracoes.limite_valor_desconto_alto'   => 'nullable|numeric|min:0',
            'configuracoes.max_parcelas'                 => 'nullable|integer|min:1|max:60',
            'configuracoes.valor_minimo_parcela'         => 'nullable|numeric|min:0',

            // IA
            'use_ai'    => 'sometimes|boolean',
            'ai_model'  => 'nullable|string|in:gpt-4o,gpt-4o-mini,gpt-4-turbo,gpt-3.5-turbo',
            'ai_prompt' => 'nullable|string|max:5000',

            // Integrações (Retell AI + OpenAI)
            'integracoes'                                       => 'sometimes|array',
            'integracoes.integracao_retell_api_key'             => 'nullable|string|max:500',
            'integracoes.integracao_retell_agent_id'            => 'nullable|string|max:255',
            'integracoes.integracao_retell_agent_id_sales'      => 'nullable|string|max:255',
            'integracoes.integracao_retell_webhook_secret'      => 'nullable|string|max:500',
            'integracoes.integracao_retell_from_number'         => 'nullable|string|max:20',
            'integracoes.integracao_openai_api_key'             => 'nullable|string|max:500',
            'integracoes.integracao_twilio_voice'               => 'nullable|string|max:100',
            'integracoes.integracao_twilio_speech_rate'         => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return ApiResponseService::validationError($validator->errors()->toArray());
        }

        // Atualizar perfil da empresa
        $camposPerfil = ['nome', 'cnpj', 'email', 'telefone', 'endereco', 'logo_url'];
        foreach ($camposPerfil as $campo) {
            if ($request->has($campo)) {
                $empresa->{$campo} = $request->input($campo);
            }
        }

        // Atualizar campos de IA
        if ($request->has('use_ai')) {
            $empresa->use_ai = (bool) $request->input('use_ai');
        }
        if ($request->has('ai_model')) {
            $empresa->ai_model = $request->input('ai_model');
        }
        if ($request->has('ai_prompt')) {
            $empresa->ai_prompt = $request->input('ai_prompt') ?: null;
        }

        $empresa->save();

        // Atualizar configurações (termos da fila) na tabela empresa_configuracoes
        if ($request->has('configuracoes')) {
            $novasConfig = $request->input('configuracoes');
            EmpresaConfiguracao::updateOrCreate(
                ['empresa_id' => $empresa->id],
                $novasConfig
            );
        }

        // Atualizar integrações na tabela empresa_integracoes
        if ($request->has('integracoes')) {
            $integracoes = $request->input('integracoes');

            // Não sobrescrever com valores mascarados (contêm •) ou vazios
            foreach ($integracoes as $chave => $valor) {
                if ($valor === null || $valor === '' || strpos($valor, '•') !== false) {
                    unset($integracoes[$chave]);
                }
            }

            if (!empty($integracoes)) {
                // Converter chaves API → colunas DB
                $dbData = EmpresaIntegracao::mapApiToDb($integracoes);

                // Criptografar campos sensíveis
                $dbData = IntegracaoService::encryptCredentials($dbData);

                $integracao = EmpresaIntegracao::firstOrNew(['empresa_id' => $empresa->id]);
                foreach ($dbData as $col => $val) {
                    $integracao->{$col} = $val;
                }
                $integracao->empresa_id = $empresa->id;
                $integracao->save();
            }
        }

        // Recarregar relationships
        $empresa->load(['configuracao', 'integracao']);

        $configuracao = $empresa->configuracao ?? EmpresaConfiguracao::getForEmpresa($empresa->id);
        $integracoesMascaradas = IntegracaoService::maskCredentials($empresa->integracao);

        return ApiResponseService::success([
            'empresa' => [
                'id'             => $empresa->id,
                'nome'           => $empresa->nome,
                'cnpj'           => $empresa->cnpj,
                'email'          => $empresa->email,
                'telefone'       => $empresa->telefone,
                'endereco'       => $empresa->endereco,
                'logo_url'       => $empresa->logo_url,
                'use_ai'         => (bool) $empresa->use_ai,
                'ai_model'       => $empresa->ai_model,
                'ai_prompt'      => $empresa->ai_prompt,
                'configuracoes'  => [
                    'nome_credora'               => $configuracao->nome_credora,
                    'nome_atendente'             => $configuracao->nome_atendente,
                    'artigo_empresa'             => $configuracao->artigo_empresa,
                    'modo_ligacao'               => $configuracao->modo_ligacao ?? 'ivr',
                    'percentual_desconto_alto'   => $configuracao->percentual_desconto_alto,
                    'percentual_desconto_baixo'  => $configuracao->percentual_desconto_baixo,
                    'limite_valor_desconto_alto'  => $configuracao->limite_valor_desconto_alto,
                    'max_parcelas'               => $configuracao->max_parcelas,
                    'valor_minimo_parcela'       => $configuracao->valor_minimo_parcela,
                ],
                'integracoes'    => $integracoesMascaradas,
                'has_own_credentials' => IntegracaoService::hasOwnCredentials($empresa->id),
            ],
        ], 'Configurações atualizadas com sucesso');
    }

    /**
     * Diagnóstico: retorna modo de ligação, agente Retell e versão atual.
     * GET /api/configuracoes/diagnostico
     */
    public function diagnostico()
    {
        if (!app()->bound('empresa_id')) {
            return ApiResponseService::forbidden('Empresa não identificada');
        }

        $empresaId = app('empresa_id');
        $empresa = Empresa::withoutGlobalScopes()->find($empresaId);

        if (!$empresa) {
            return ApiResponseService::notFound('Empresa não encontrada');
        }

        $configuracao = EmpresaConfiguracao::where('empresa_id', $empresaId)->first();
        $integracao = EmpresaIntegracao::where('empresa_id', $empresaId)->first();

        // Determinar modo exatamente como o worker faz
        $modoLigacao = $configuracao->modo_ligacao ?? (env('USE_IVR_MODE', true) ? 'ivr' : 'retell');
        $modoOrigem = $configuracao && $configuracao->modo_ligacao
            ? 'empresa_configuracoes (banco)'
            : 'env USE_IVR_MODE (fallback)';

        // Resolver credenciais exatamente como o worker faz
        $creds = IntegracaoService::getCredentials($empresaId);
        $retellAgentId = $creds['retell_agent_id'];
        $retellApiKey = $creds['retell_api_key'];
        $fromNumber = $creds['from_number'];

        $agentIdOrigem = ($integracao && !empty($integracao->retell_agent_id))
            ? 'empresa_integracoes (banco)'
            : 'env RETELL_AGENT_ID (fallback)';

        $apiKeyOrigem = ($integracao && !empty($integracao->retell_api_key))
            ? 'empresa_integracoes (banco)'
            : 'env RETELL_API_KEY (fallback)';

        // Buscar info do agente na Retell API (se modo retell)
        $retellAgentInfo = null;
        $retellError = null;

        if ($modoLigacao === 'retell' && !empty($retellApiKey) && !empty($retellAgentId)) {
            try {
                $client = new Client();
                $response = $client->get("https://api.retellai.com/get-agent/{$retellAgentId}", [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $retellApiKey,
                        'Content-Type'  => 'application/json',
                    ],
                    'timeout' => 10,
                ]);

                $agentData = json_decode($response->getBody()->getContents(), true);
                $retellAgentInfo = [
                    'agent_id'       => $agentData['agent_id'] ?? null,
                    'agent_name'     => $agentData['agent_name'] ?? null,
                    'version'        => $agentData['version'] ?? null,
                    'llm_id'         => $agentData['response_engine']['llm_id'] ?? ($agentData['llm_websocket_url'] ?? null),
                    'voice_id'       => $agentData['voice_id'] ?? null,
                    'language'       => $agentData['language'] ?? null,
                    'last_modified'  => $agentData['last_modification_timestamp'] ?? null,
                ];
            } catch (\Exception $e) {
                $retellError = $e->getMessage();
            }
        }

        // Credenciais Twilio (se modo IVR)
        $twilioInfo = null;
        if ($modoLigacao === 'ivr') {
            $twilioCreds = IntegracaoService::getTwilioCredentials($empresaId);
            $twilioInfo = [
                'from_number' => $twilioCreds['twilio_from_number'] ?? null,
                'voice'       => $twilioCreds['twilio_voice'] ?? null,
                'speech_rate' => $twilioCreds['twilio_speech_rate'] ?? null,
                'has_sid'     => !empty($twilioCreds['twilio_account_sid']),
                'has_token'   => !empty($twilioCreds['twilio_auth_token']),
            ];
        }

        $resultado = [
            'empresa' => [
                'id'   => $empresa->id,
                'nome' => $empresa->nome,
            ],
            'modo_ligacao' => [
                'valor'  => $modoLigacao,
                'origem' => $modoOrigem,
            ],
            'retell' => [
                'agent_id'        => $retellAgentId,
                'agent_id_origem' => $agentIdOrigem,
                'api_key_origem'  => $apiKeyOrigem,
                'has_api_key'     => !empty($retellApiKey),
                'from_number'     => $fromNumber,
                'agent_info'      => $retellAgentInfo,
                'erro'            => $retellError,
            ],
            'twilio' => $twilioInfo,
            'config_existe' => [
                'empresa_configuracoes' => $configuracao !== null,
                'empresa_integracoes'   => $integracao !== null,
            ],
            'env_fallbacks' => [
                'USE_IVR_MODE'    => env('USE_IVR_MODE', 'não definido'),
                'RETELL_AGENT_ID' => !empty(env('RETELL_AGENT_ID')) ? env('RETELL_AGENT_ID') : 'não definido',
                'has_RETELL_API_KEY' => !empty(env('RETELL_API_KEY')),
            ],
        ];


        return ApiResponseService::success($resultado);
    }
}
