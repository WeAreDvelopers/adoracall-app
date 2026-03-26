<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use App\Models\Contato;
use App\Models\Empresa;
use App\Models\EmpresaConfiguracao;
use App\Models\Ligacao;
use App\Services\IntegracaoService;
use App\Services\SecurityService;
use App\Services\TwilioUraService;
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

        // Verificar modo de ligação da empresa
        $empresaId = app()->bound('empresa_id') ? app('empresa_id') : null;
        $config = $empresaId ? EmpresaConfiguracao::getForEmpresa($empresaId) : null;
        $modo = $config?->modo_ligacao ?? 'ivr';


        if ($modo === 'ivr') {
            return $this->startIvrCall($request, $validated, $empresaId);
        }


        // Modo Retell
        $creds   = IntegracaoService::getCredentials($empresaId);
        $apiKey  = $creds['retell_api_key'];
        $agentId = $creds['retell_agent_id'];
        $from    = $creds['from_number'];
        $nomeAtendente = env('AGENT_NAME', 'Angélica');
        $numeroEmpresa = env('COMPANY_PHONE', '(11) 3333-4444');

        // DEBUG: Verificar se API key está sendo carregada

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


            $client = new Client();

            // Criar chamada via Retell API
            $retellUrl = 'https://api.retellai.com/v2/create-phone-call';

            // Preparar variáveis dinâmicas (nomes devem coincidir com o agente Retell)
            $configuracao = EmpresaConfiguracao::getForEmpresa($empresaId);
            $desconto = ($validated['percentual_desconto'] ?? $configuracao->percentual_desconto_alto ?? 10) / 100;
            $valorComDesconto = $validated['valor_devido'] * (1 - $desconto);
            $numParcelas = (int)($validated['max_parcelas'] ?? $configuracao->max_parcelas ?? 3);

            // CPF limpo para enviar ao Retell (função BuscaAcordo usa {{cpf}})
            $cpfLimpo = $this->securityService->limparCpf($validated['cpf']);

            $dynamicVariables = [
                'customer_id'             => (string) $contato->id,
                'nome_cliente'            => $validated['primeiro_nome'],
                'sobrenome'               => $validated['sobrenome'] ?? '',
                'credora'                 => $empresaCredora ?? 'Empresa',
                'cpf'                     => $cpfLimpo,
                'documento'               => $primeirosDigitosCpf,
                'valor_devido'            => number_format($validated['valor_devido'], 2, ',', '.'),
                'data_vencimento'         => $validated['data_vencimento'],
                'percentual_desconto'     => round($desconto * 100) . '%',
                'valor_com_desconto'      => $validated['valor_com_desconto'] ?: number_format($valorComDesconto, 2, ',', '.'),
                'max_parcelas'            => (string) $numParcelas,
                'valor_parcela'           => $validated['valor_parcela'] ?: number_format($valorComDesconto / max($numParcelas, 1), 2, ',', '.'),
                'campanha'                => $validated['campanha'] ?? 'Cobrança ' . date('m/Y'),
                'historico_inadimplencia' => 'primeira_vez',
                'tentativas_contato'      => (string) ($contato->tentativas_contato ?? 1),
            ];

            $requestBody = [
                'from_number'                  => $from,
                'to_number'                    => $validated['to'],
                'override_agent_id'            => $agentId,
                'retell_llm_dynamic_variables' => $dynamicVariables,
                'metadata'                     => [
                    'contato_id'    => $contato->id,
                    'customer_name' => $contato->nome_completo,
                    'company'       => $empresaCredora,
                    'debt_amount'   => $validated['valor_devido'],
                    'tipo_chamada'  => 'cobranca',
                    'empresa_id'    => $contato->empresa_id,
                ],
            ];


            $retellResponse = $client->post($retellUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => $requestBody,
            ]);

            $retellBody = json_decode($retellResponse->getBody()->getContents(), true);


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
     * Inicia chamada via IVR (Twilio TwiML).
     */
    private function startIvrCall(Request $request, array $validated, ?int $empresaId)
    {
        try {
            DB::beginTransaction();

            // Buscar nome da credora
            $empresaCredora = $validated['empresa_credora'] ?? null;
            if (!$empresaCredora && $empresaId) {
                $empresa = Empresa::withoutGlobalScopes()->find($empresaId);
                if ($empresa) {
                    $empresaCredora = $empresa->nome_credora;
                }
            }

            // Criptografar CPF
            $cpfCriptografado = $this->securityService->encryptCpf($validated['cpf']);
            $primeirosDigitosCpf = $this->securityService->getPrimeirosDigitosCpf($validated['cpf']);

            // Criar/atualizar contato
            $contato = Contato::updateOrCreate(
                [
                    'telefone'   => $validated['to'],
                    'empresa_id' => $empresaId,
                ],
                [
                    'nome'                => $validated['primeiro_nome'],
                    'sobrenome'           => $validated['sobrenome'] ?? null,
                    'cpf'                 => $cpfCriptografado,
                    'cpf_primeiros_digitos' => $primeirosDigitosCpf,
                    'data_nascimento'     => $validated['data_nascimento'],
                    'valor_debito'        => $validated['valor_devido'],
                    'vencimento'          => $validated['data_vencimento'],
                    'empresa_credora'     => $empresaCredora,
                    'campanha'            => $validated['campanha'] ?? 'Cobrança ' . date('m/Y'),
                    'status'              => 'em_ligacao',
                ]
            );

            $contato->incrementarTentativas();


            // Iniciar chamada via TwilioUraService
            $uraService = app(TwilioUraService::class);
            $result = $uraService->initiateCall($contato, $empresaId, null, null);

            DB::commit();

            return response()->json([
                'success'     => true,
                'call_id'     => $result['call_sid'] ?? null,
                'call_status' => 'iniciada',
                'contato_id'  => $contato->id,
                'ligacao_id'  => $result['ligacao_id'] ?? null,
                'message'     => 'Ligação iniciada com sucesso via IVR.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("[IVR-MANUAL] Erro: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'error'   => ['message' => $e->getMessage()],
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
