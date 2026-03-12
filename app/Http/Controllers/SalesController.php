<?php
namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LigacaoVenda;
use App\Services\IntegracaoService;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SalesController extends Controller
{
    /**
     * Inicia chamada de vendas via Retell AI
     *
     * POST /api/ura/call/vendas
     */
    public function startSalesCall(Request $request)
    {
        $validated = $this->validate($request, [
            'to'            => 'required|string',
            'primeiro_nome' => 'required|string',
            'sobrenome'     => 'nullable|string',
            'email'         => 'nullable|email',
            'produto'       => 'required|string',
            'origem'        => 'nullable|string',
            'observacoes'   => 'nullable|string',
            'campanha'      => 'nullable|string',
            'tag'           => 'nullable|string',
        ]);

        $empresaId       = app()->bound('empresa_id') ? app('empresa_id') : null;
        $creds           = IntegracaoService::getCredentials($empresaId);
        $apiKey          = $creds['retell_api_key'];
        $agentId         = $creds['retell_agent_id_sales'] ?: $creds['retell_agent_id'];
        $from            = $creds['from_number'];
        $nomeVendedor    = env('SALES_AGENT_NAME', 'Samantha');
        $empresaNome     = env('COMPANY_NAME', 'Dvelopers Soluções');
        $empresaTelefone = env('COMPANY_PHONE', '(11) 3333-4444');

        // Validar se agent de vendas está configurado
        if (! $agentId) {
            return response()->json([
                'success' => false,
                'error'   => ['message' => 'Agente de vendas não configurado. Configure RETELL_AGENT_ID_SALES no .env'],
            ], 500);
        }

        try {
            DB::beginTransaction();

            // Processar tags
            $tags = [];
            if (! empty($validated['tag'])) {
                $tags = array_map('trim', explode(',', $validated['tag']));
            }

            // Criar ou atualizar lead (filtrando também por empresa_id)
            $lead = Lead::updateOrCreate(
                [
                    'telefone'   => $validated['to'],
                    'empresa_id' => $empresaId,
                ],
                [
                    'nome'        => $validated['primeiro_nome'],
                    'sobrenome'   => $validated['sobrenome'] ?? null,
                    'email'       => $validated['email'] ?? null,
                    'produto'     => $validated['produto'],
                    'origem'      => $validated['origem'] ?? 'manual',
                    'observacoes' => $validated['observacoes'] ?? null,
                    'campanha'    => $validated['campanha'] ?? 'Vendas ' . date('m/Y'),
                    'tags'        => $tags,
                    'status'      => 'em_ligacao',
                ]
            );

            $lead->incrementarTentativas();


            $client = new Client();

            // Criar chamada via Retell API
            $retellUrl = 'https://api.retellai.com/v2/create-phone-call';

            // Preparar informações sobre o produto
            $produtoInfo = $this->getProdutoInfo($validated['produto']);

            // Preparar variáveis dinâmicas para o agente de vendas
            $dynamicVariables = [
                'primeiro_nome'      => $validated['primeiro_nome'],
                'sobrenome'          => $validated['sobrenome'] ?? '',
                'nome_vendedor'      => $nomeVendedor,
                'empresa_nome'       => $empresaNome,
                'empresa_telefone'   => $empresaTelefone,
                'produto_nome'       => $produtoInfo['nome'],
                'produto_descricao'  => $produtoInfo['descricao'],
                'produto_beneficios' => $produtoInfo['beneficios'],
                'produto_preco'      => $produtoInfo['preco'],
                'origem_lead'        => $validated['origem'] ?? 'site',
                'observacoes'        => $validated['observacoes'] ?? '',
                // Variáveis que serão preenchidas durante a conversa:
                'nivel_interesse'    => '',
                'objecoes'           => '',
                'proxima_acao'       => '',
                'data_follow_up'     => '',
                'hora_follow_up'     => '',
            ];

            $requestBody = [
                'from_number'       => $from,
                'to_number'         => $validated['to'],
                'override_agent_id' => $agentId,
                'dynamic_variables' => $dynamicVariables,
            ];

            // Adicionar metadados para rastreamento (incluindo lead_id e empresa_id)
            $requestBody['metadata'] = [
                'lead_id'       => $lead->id,
                'prospect_name' => $lead->nome_completo,
                'produto'       => $validated['produto'],
                'campanha'      => $lead->campanha,
                'tipo_chamada'  => 'vendas',
                'empresa_id'    => $lead->empresa_id,
            ];


            $retellResponse = $client->post($retellUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json'    => $requestBody,
            ]);

            $retellBody = json_decode($retellResponse->getBody()->getContents(), true);


            // Criar registro de ligação de venda
            $ligacao = LigacaoVenda::create([
                'lead_id'        => $lead->id,
                'empresa_id'     => $lead->empresa_id,
                'call_id_retell' => $retellBody['call_id'] ?? null,
                'status'         => $retellBody['call_status'] ?? 'iniciada',
                'detalhes'       => [
                    'agent_id'          => $retellBody['agent_id'] ?? $agentId,
                    'request_timestamp' => \Carbon\Carbon::now()->toIso8601String(),
                    'dynamic_variables' => $dynamicVariables,
                    'produto_info'      => $produtoInfo,
                ],
            ]);


            DB::commit();

            return response()->json([
                'success'     => true,
                'call_id'     => $retellBody['call_id'] ?? null,
                'call_status' => $retellBody['call_status'] ?? null,
                'agent_id'    => $retellBody['agent_id'] ?? null,
                'lead_id'     => $lead->id,
                'ligacao_id'  => $ligacao->id,
                'message'     => 'Ligação de vendas iniciada com sucesso via Retell AI.',
            ]);

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            DB::rollBack();
            $errorBody = $e->hasResponse() ? json_decode($e->getResponse()->getBody()->getContents(), true) : null;

            Log::error('❌ Retell API Error (Sales): ' . json_encode($errorBody));

            return response()->json([
                'success' => false,
                'error'   => $errorBody ?? ['message' => $e->getMessage()],
            ], $e->getCode() ?: 400);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Exception (Sales): ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'error'   => ['message' => $e->getMessage()],
            ], 500);
        }
    }

    /**
     * Retorna informações sobre o produto/serviço
     */
    private function getProdutoInfo(string $produto): array
    {
        $produtos = [
            'plano_basico'        => [
                'nome'       => 'Plano Básico',
                'descricao'  => 'Solução ideal para começar com as funcionalidades essenciais',
                'beneficios' => 'Acesso a funcionalidades básicas, suporte via email, atualizações mensais',
                'preco'      => 'R$ 99,00 por mês',
            ],
            'plano_intermediario' => [
                'nome'       => 'Plano Intermediário',
                'descricao'  => 'Perfeito para empresas em crescimento que precisam de mais recursos',
                'beneficios' => 'Todas as funcionalidades básicas, integrações avançadas, suporte prioritário, relatórios personalizados',
                'preco'      => 'R$ 199,00 por mês',
            ],
            'plano_premium'       => [
                'nome'       => 'Plano Premium',
                'descricao'  => 'Solução completa para empresas que buscam máximo desempenho',
                'beneficios' => 'Todas as funcionalidades, suporte 24/7, gerente de conta dedicado, customizações ilimitadas, SLA garantido',
                'preco'      => 'R$ 399,00 por mês',
            ],
            'consultoria'         => [
                'nome'       => 'Consultoria Especializada',
                'descricao'  => 'Consultoria personalizada com especialistas para seu negócio',
                'beneficios' => 'Análise completa, planejamento estratégico, implementação customizada, acompanhamento mensal',
                'preco'      => 'A partir de R$ 2.500,00',
            ],
            'outro'               => [
                'nome'       => 'Solução Personalizada',
                'descricao'  => 'Solução desenvolvida especificamente para suas necessidades',
                'beneficios' => 'Totalmente customizável de acordo com suas necessidades',
                'preco'      => 'Sob consulta',
            ],
        ];

        return $produtos[$produto] ?? [
            'nome'       => ucfirst(str_replace('_', ' ', $produto)),
            'descricao'  => 'Produto ou serviço personalizado',
            'beneficios' => 'Benefícios customizados',
            'preco'      => 'Sob consulta',
        ];
    }

    /**
     * Retorna informações do lead para o Retell
     */
    public function getLeadInfo($callId)
    {
        try {
            $ligacao = LigacaoVenda::where('call_id_retell', $callId)->firstOrFail();
            $lead    = $ligacao->lead;

            return response()->json([
                'success' => true,
                'lead'    => [
                    'primeiro_nome' => $lead->primeiro_nome,
                    'sobrenome'     => $lead->sobrenome,
                    'email'         => $lead->email,
                    'produto'       => $lead->produto,
                    'origem'        => $lead->origem,
                    'observacoes'   => $lead->observacoes,
                ],
                'ligacao' => [
                    'id'     => $ligacao->id,
                    'status' => $ligacao->status,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Erro ao buscar informações do lead: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error'   => ['message' => 'Lead ou ligação não encontrado'],
            ], 404);
        }
    }

    /**
     * Endpoint para registrar interesse do lead durante a chamada
     * Pode ser chamado via Custom LLM Function do Retell
     */
    public function registrarInteresse(Request $request)
    {
        $validated = $this->validate($request, [
            'call_id'         => 'required|string',
            'lead_id'         => 'required|integer',
            'interessado'     => 'required|boolean',
            'nivel_interesse' => 'nullable|string|in:frio,morno,quente,muito_quente',
            'objecoes'        => 'nullable|array',
            'proximos_passos' => 'nullable|string',
            'follow_up_data'  => 'nullable|date',
        ]);

        try {
            $lead    = Lead::findOrFail($validated['lead_id']);
            $ligacao = LigacaoVenda::where('call_id_retell', $validated['call_id'])
                ->where('lead_id', $lead->id)
                ->firstOrFail();

            // Atualizar ligação
            $ligacao->registrarInteresse(
                $validated['interessado'],
                $validated['objecoes'] ?? []
            );

            if (! empty($validated['proximos_passos'])) {
                $ligacao->proximos_passos = $validated['proximos_passos'];
                $ligacao->save();
            }

            // Atualizar lead
            if ($validated['interessado']) {
                $lead->status = 'interessado';
                if (! empty($validated['nivel_interesse'])) {
                    $lead->atualizarInteresse($validated['nivel_interesse']);
                }
            } else {
                $lead->status = 'nao_interessado';
            }

            if (! empty($validated['follow_up_data'])) {
                $lead->data_proxima_acao = $validated['follow_up_data'];
                $lead->proxima_acao      = $validated['proximos_passos'] ?? 'Follow-up';
            }

            $lead->save();


            return response()->json([
                'success' => true,
                'message' => 'Interesse registrado com sucesso',
                'lead'    => [
                    'id'              => $lead->id,
                    'status'          => $lead->status,
                    'interesse_nivel' => $lead->interesse_nivel,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erro ao registrar interesse: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error'   => ['message' => 'Erro ao registrar interesse'],
            ], 500);
        }
    }

    /**
     * Registrar qualificação do lead (score e temperatura)
     * Webhook chamado pelo Retell AI durante a conversa
     *
     * POST /api/ura/vendas/registrar-qualificacao
     */
    public function registrarQualificacao(Request $request)
    {
        $validated = $this->validate($request, [
            'call_id'     => 'required|string',
            'score'       => 'required|numeric|min:0|max:10',
            'temperatura' => 'required|string|in:quente,morno,frio',
            'observacoes' => 'nullable|string',
        ]);

        try {
            // Buscar ligação pelo call_id do Retell
            $ligacao = LigacaoVenda::where('call_id_retell', $validated['call_id'])
                ->firstOrFail();

            $lead = $ligacao->lead;

            // Mapear temperatura para nivel_interesse
            $nivelInteresseMap = [
                'quente' => 'muito_quente', // score 8-10
                'morno'  => 'morno',        // score 5-7
                'frio'   => 'frio',         // score 0-4
            ];

            $nivelInteresse = $nivelInteresseMap[$validated['temperatura']];

            // Atualizar lead com o score e temperatura
            $lead->interesse_nivel = $nivelInteresse;
            $lead->interesse_score = $validated['score'];

            if ($validated['score'] >= 8) {
                $lead->status = 'interessado';
            } elseif ($validated['score'] >= 5) {
                $lead->status = 'em_analise';
            } else {
                $lead->status = 'pouco_interesse';
            }

            if (! empty($validated['observacoes'])) {
                $lead->observacoes = ($lead->observacoes ?? '') . "\n\n[Qualificação]: " . $validated['observacoes'];
            }

            $lead->save();

            // Atualizar detalhes da ligação
            $detalhes                 = $ligacao->detalhes ?? [];
            $detalhes['qualificacao'] = [
                'score'           => $validated['score'],
                'temperatura'     => $validated['temperatura'],
                'nivel_interesse' => $nivelInteresse,
                'observacoes'     => $validated['observacoes'] ?? null,
                'timestamp'       => Carbon::now()->toIso8601String(),
            ];
            $ligacao->detalhes = $detalhes;
            $ligacao->save();


            return response()->json([
                'success'      => true,
                'message'      => 'Qualificação registrada com sucesso',
                'lead'         => [
                    'id'              => $lead->id,
                    'status'          => $lead->status,
                    'interesse_nivel' => $lead->interesse_nivel,
                    'interesse_score' => $lead->interesse_score,
                ],
                'qualificacao' => [
                    'score'       => $validated['score'],
                    'temperatura' => $validated['temperatura'],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erro ao registrar qualificação: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'error'   => ['message' => 'Erro ao registrar qualificação: ' . $e->getMessage()],
            ], 500);
        }
    }

    /**
     * Registrar próximos passos definidos com o lead
     * Webhook chamado pelo Retell AI durante a conversa
     *
     * POST /api/ura/vendas/registrar-proximos-passos
     */
    public function registrarProximosPassos(Request $request)
    {
        $validated = $this->validate($request, [
            'call_id'           => 'required|string',
            'acao_definida'     => 'required|string',
            'data_followup'     => 'nullable|date',
            'horario_followup'  => 'nullable|string',
            'canal_preferencia' => 'required|string|in:whatsapp,email,telefone,videochamada',
            'motivo'            => 'nullable|string',
            'observacoes'       => 'nullable|string',
        ]);

        try {
            // Buscar ligação pelo call_id do Retell
            $ligacao = LigacaoVenda::where('call_id_retell', $validated['call_id'])
                ->firstOrFail();

            $lead = $ligacao->lead;

            // Atualizar próximos passos no lead
            $lead->proxima_acao = $validated['acao_definida'];

            if (! empty($validated['data_followup'])) {
                $dataFollowup = $validated['data_followup'];

                // Se tiver horário, combinar data + hora
                if (! empty($validated['horario_followup'])) {
                    $dataFollowup .= ' ' . $validated['horario_followup'];
                }

                $lead->data_proxima_acao = $dataFollowup;
            }

            if (! empty($validated['observacoes'])) {
                $lead->observacoes = ($lead->observacoes ?? '') . "\n\n[Próximos Passos]: " . $validated['observacoes'];
            }

            $lead->save();

            // Atualizar detalhes da ligação
            $detalhes                    = $ligacao->detalhes ?? [];
            $detalhes['proximos_passos'] = [
                'acao_definida'     => $validated['acao_definida'],
                'data_followup'     => $validated['data_followup'] ?? null,
                'horario_followup'  => $validated['horario_followup'] ?? null,
                'canal_preferencia' => $validated['canal_preferencia'],
                'motivo'            => $validated['motivo'] ?? null,
                'observacoes'       => $validated['observacoes'] ?? null,
                'timestamp'         => Carbon::now()->toIso8601String(),
            ];

            $ligacao->detalhes        = $detalhes;
            $ligacao->proximos_passos = $validated['acao_definida'];
            $ligacao->save();


            return response()->json([
                'success'         => true,
                'message'         => 'Próximos passos registrados com sucesso',
                'lead'            => [
                    'id'                => $lead->id,
                    'proxima_acao'      => $lead->proxima_acao,
                    'data_proxima_acao' => $lead->data_proxima_acao,
                ],
                'proximos_passos' => [
                    'acao_definida'     => $validated['acao_definida'],
                    'data_followup'     => $validated['data_followup'] ?? null,
                    'horario_followup'  => $validated['horario_followup'] ?? null,
                    'canal_preferencia' => $validated['canal_preferencia'],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erro ao registrar próximos passos: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'error'   => ['message' => 'Erro ao registrar próximos passos: ' . $e->getMessage()],
            ], 500);
        }
    }
}
