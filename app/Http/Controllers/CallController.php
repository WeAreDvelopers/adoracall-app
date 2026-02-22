<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use App\Models\Contato;
use App\Models\Empresa;
use App\Models\Ligacao;
use App\Services\IntegracaoService;
use App\Services\SecurityService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CallController extends Controller
{
    protected $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    public function startCall(Request $request)
    {
        // Se for chamada de vendas, delegar para SalesController
        if ($request->input('tipo') === 'vendas') {
            Log::info('🎯 Chamada de vendas detectada - delegando para SalesController');

            $salesController = app()->make('App\Http\Controllers\SalesController');
            return $salesController->startSalesCall($request);
        }

        // Validação para chamadas de cobrança
        $validated = $this->validate($request, [
            'to'   => 'required|string',
            'primeiro_nome' => 'required|string',
            'sobrenome' => 'nullable|string',
            'cpf' => 'required|string',
            'data_nascimento' => 'required|date',
            'empresa_credora' => 'nullable|string',
            'valor_devido' => 'required|numeric',
            'data_vencimento' => 'required|string',
            'percentual_desconto' => 'nullable|string',
            'valor_com_desconto' => 'nullable|string',
            'max_parcelas' => 'nullable|integer',
            'valor_parcela' => 'nullable|string',
            'max_parcelas_estendidas' => 'nullable|integer',
            'campanha' => 'nullable|string',
        ]);

        // Validar CPF
        if (!$this->securityService->validarCpf($validated['cpf'])) {
            return response()->json([
                'success' => false,
                'error' => ['message' => 'CPF inválido']
            ], 400);
        }

        // Validar data de nascimento
        if (!$this->securityService->validarDataNascimento($validated['data_nascimento'])) {
            return response()->json([
                'success' => false,
                'error' => ['message' => 'Data de nascimento inválida']
            ], 400);
        }

        $creds   = IntegracaoService::getCredentials(app()->bound('empresa_id') ? app('empresa_id') : null);
        $apiKey  = $creds['retell_api_key'];
        $agentId = $creds['retell_agent_id'];
        $from    = $creds['from_number'];
        $nomeAtendente = env('AGENT_NAME', 'Angélica');
        $numeroEmpresa = env('COMPANY_PHONE', '(11) 3333-4444');

        // DEBUG: Verificar se API key está sendo carregada
        Log::info('🔐 [RETELL-DEBUG] API Key carregada: ' . (empty($apiKey) ? 'VAZIA!' : substr($apiKey, 0, 10) . '...'));
        Log::info('🤖 [RETELL-DEBUG] Agent ID carregada: ' . (empty($agentId) ? 'VAZIO!' : $agentId));
        Log::info('📞 [RETELL-DEBUG] From Number carregada: ' . (empty($from) ? 'VAZIO!' : $from));

        try {
            DB::beginTransaction();

            // Buscar nome da credora da empresa logada
            $empresaCredora = $validated['empresa_credora'] ?? null;
            if (!$empresaCredora && app()->bound('empresa_id')) {
                $empresa = Empresa::withoutGlobalScopes()->find(app('empresa_id'));
                if ($empresa) {
                    $empresaCredora = $empresa->nome_credora;
                }
            }

            // Criptografar CPF e extrair primeiros dígitos
            $cpfCriptografado = $this->securityService->encryptCpf($validated['cpf']);
            $primeirosDigitosCpf = $this->securityService->getPrimeirosDigitosCpf($validated['cpf']);

            // Criar ou atualizar contato (filtrando também por empresa_id)
            $empresaId = app()->bound('empresa_id') ? app('empresa_id') : null;
            $contato = Contato::updateOrCreate(
                [
                    'telefone' => $validated['to'],
                    'empresa_id' => $empresaId,
                ],
                [
                    'nome' => $validated['primeiro_nome'],
                    'sobrenome' => $validated['sobrenome'] ?? null,
                    'cpf' => $cpfCriptografado,
                    'cpf_primeiros_digitos' => $primeirosDigitosCpf,
                    'data_nascimento' => $validated['data_nascimento'],
                    'valor_debito' => $validated['valor_devido'],
                    'vencimento' => $validated['data_vencimento'],
                    'empresa_credora' => $empresaCredora,
                    'campanha' => $validated['campanha'] ?? 'Cobrança ' . date('m/Y'),
                    'status' => 'em_ligacao',
                ]
            );

            $contato->incrementarTentativas();

            Log::info('📞 Iniciando chamada via Retell AI...');
            Log::info('📱 From: ' . $from . ' | To: ' . $validated['to']);
            Log::info('🤖 Agent ID: ' . $agentId);
            Log::info('👤 Cliente: ' . $contato->nome_completo);
            Log::info('🆔 Contato ID: ' . $contato->id);

            $client = new Client();

            // Criar chamada via Retell API
            $retellUrl = 'https://api.retellai.com/v2/create-phone-call';

            // Preparar variáveis dinâmicas para o agente
            $dynamicVariables = [
                'primeiro_nome' => $validated['primeiro_nome'],
                'sobrenome' => $validated['sobrenome'] ?? '',
                'empresa_credora' => $empresaCredora ?? 'Empresa',
                'valor_devido' => number_format($validated['valor_devido'], 2, ',', '.'),
                'data_vencimento' => $validated['data_vencimento'],
                'nome_atendente' => $nomeAtendente,
                'numero_empresa' => $numeroEmpresa,
                'percentual_desconto' => $validated['percentual_desconto'] ?? '10',
                'valor_com_desconto' => $validated['valor_com_desconto'] ?? '',
                'max_parcelas' => (string)($validated['max_parcelas'] ?? 3),
                'valor_parcela' => $validated['valor_parcela'] ?? '',
                'max_parcelas_estendidas' => (string)($validated['max_parcelas_estendidas'] ?? 6),
                'canal_envio' => '',
                'num_parcelas' => '',
                'condições_acordo_especial' => '',
                'data_retorno' => '',
                'hora_retorno' => '',
            ];

            $requestBody = [
                'from_number' => $from,
                'to_number'   => $validated['to'],
                'override_agent_id' => $agentId,
                'dynamic_variables' => $dynamicVariables,
            ];

            // Adicionar metadados para rastreamento (incluindo contato_id e empresa_id)
            $requestBody['metadata'] = [
                'contato_id' => $contato->id,
                'customer_name' => $contato->nome_completo,
                'company' => $validated['empresa_credora'],
                'debt_amount' => $validated['valor_devido'],
                'tipo_chamada' => 'cobranca',
                'empresa_id' => $contato->empresa_id,
            ];

            Log::info('📤 Request body: ' . json_encode($requestBody));

            $retellResponse = $client->post($retellUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => $requestBody,
            ]);

            $retellBody = json_decode($retellResponse->getBody()->getContents(), true);

            Log::info('✅ Retell API Response: ' . json_encode($retellBody));

            // Criar registro de ligação
            $ligacao = Ligacao::create([
                'contato_id' => $contato->id,
                'empresa_id' => $contato->empresa_id,
                'call_id_retell' => $retellBody['call_id'] ?? null,
                'status' => $retellBody['call_status'] ?? 'iniciada',
                'detalhes' => [
                    'agent_id' => $retellBody['agent_id'] ?? $agentId,
                    'request_timestamp' => Carbon::now()->toIso8601String(),
                    'dynamic_variables' => $dynamicVariables,
                ]
            ]);

            Log::info('💾 Ligação registrada: ID ' . $ligacao->id);

            DB::commit();

            return response()->json([
                'success'     => true,
                'call_id'     => $retellBody['call_id'] ?? null,
                'call_status' => $retellBody['call_status'] ?? null,
                'agent_id'    => $retellBody['agent_id'] ?? null,
                'contato_id'  => $contato->id,
                'ligacao_id'  => $ligacao->id,
                'message'     => 'Ligação iniciada com sucesso via Retell AI.',
            ]);

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            DB::rollBack();
            $errorBody = $e->hasResponse() ? json_decode($e->getResponse()->getBody()->getContents(), true) : null;

            Log::error('❌ Retell API Error: ' . json_encode($errorBody));

            return response()->json([
                'success' => false,
                'error'   => $errorBody ?? ['message' => $e->getMessage()],
            ], $e->getCode() ?: 400);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Exception: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'error' => ['message' => $e->getMessage()],
            ], 500);
        }
    }

    /**
     * Retorna informações da chamada para o Retell
     */
    public function getCallInfo($callId)
    {
        try {
            $ligacao = Ligacao::where('call_id_retell', $callId)->firstOrFail();
            $contato = $ligacao->contato;

            return response()->json([
                'success' => true,
                'contato' => [
                    'primeiro_nome' => $contato->primeiro_nome,
                    'sobrenome' => $contato->sobrenome,
                    'valor_devido' => number_format($contato->valor_debito, 2, ',', '.'),
                    'data_vencimento' => $contato->vencimento->format('d \d\e F'),
                    'empresa_credora' => $contato->empresa_credora,
                ],
                'ligacao' => [
                    'id' => $ligacao->id,
                    'status' => $ligacao->status,
                    'tentativas_validacao' => $ligacao->tentativas_validacao,
                    'validacao_sucesso' => $ligacao->validacao_sucesso,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Erro ao buscar informações da chamada: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => ['message' => 'Chamada não encontrada'],
            ], 404);
        }
    }
}
