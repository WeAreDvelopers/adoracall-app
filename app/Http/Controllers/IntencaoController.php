<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Services\PagamentoService;
use App\Models\Contato;
use App\Models\IntencaoScript;
use App\Models\ScriptLog;

/**
 * Controller para processar intenções detectadas durante ligações
 * Chamado como Custom LLM Function pelo Retell AI
 */
class IntencaoController extends Controller
{
    private PagamentoService $pagamentoService;

    public function __construct(PagamentoService $pagamentoService)
    {
        $this->pagamentoService = $pagamentoService;
    }

    /**
     * Processa uma intenção detectada durante a ligação
     * POST /api/intencoes/processar
     *
     * Esta função é chamada pelo Retell AI como Custom LLM Function
     * quando uma intenção é detectada durante a conversa
     */
    public function processar(Request $request)
    {
        try {
            Log::info('Intenção detectada', $request->all());

            // Validação
            $validator = Validator::make($request->all(), [
                'intencao' => 'required|string',
                'contato_id' => 'nullable|integer|exists:contatos,id',
                'call_id' => 'nullable|string',
                'contexto' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $intencao = $request->intencao;
            $contatoId = $request->contato_id;
            $callId = $request->call_id;
            $contexto = $request->contexto ?? [];

            // Busca o contato se fornecido
            $contato = $contatoId ? Contato::find($contatoId) : null;

            // Registra a detecção da intenção
            $this->registrarLog($callId, $intencao, $contexto, $contato);

            // Processa a intenção baseado no tipo
            $resultado = $this->processarIntencaoPorTipo($intencao, $contato, $contexto);

            return response()->json([
                'success' => true,
                'message' => $resultado['mensagem'] ?? 'Intenção processada com sucesso',
                'data' => $resultado['data'] ?? null,
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao processar intenção: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro ao processar intenção: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Custom LLM Function: Gerar e enviar proposta de pagamento
     * POST /api/intencoes/gerar-proposta-pagamento
     *
     * Chamado pelo Retell quando o cliente aceita pagar
     */
    public function gerarPropostaPagamento(Request $request)
    {
        try {
            Log::info('Gerando proposta de pagamento', $request->all());

            // Validação
            $validator = Validator::make($request->all(), [
                'contato_id' => 'required|integer|exists:contatos,id',
                'valor_proposta' => 'nullable|numeric|min:0.01',
                'tipo_proposta' => 'nullable|in:pix,boleto,cartao,link_generico',
                'desconto_percentual' => 'nullable|numeric|min:0|max:100',
                'call_id' => 'nullable|string',
                'script_id' => 'nullable|integer|exists:scripts,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Busca o contato
            $contato = Contato::findOrFail($request->contato_id);

            // Calcula o valor da proposta
            $valorOriginal = $contato->valor_debito;
            $descontoPercentual = $request->desconto_percentual ?? 0;

            if ($request->has('valor_proposta')) {
                $valorProposta = $request->valor_proposta;
            } else {
                $valorProposta = $valorOriginal * (1 - ($descontoPercentual / 100));
            }

            // Prepara opções
            $opcoes = [
                'valor_original' => $valorOriginal,
                'script_id' => $request->script_id ?? $contato->mailing->script_id ?? null,
                'metadata' => [
                    'call_id' => $request->call_id,
                    'desconto_percentual' => $descontoPercentual,
                    'processado_via' => 'retell_custom_function',
                ],
                'criado_por' => 'retell_ai',
            ];

            // Cria e envia a proposta
            $proposta = $this->pagamentoService->criarEEnviarProposta(
                $contato,
                $valorProposta,
                $request->tipo_proposta ?? 'link_generico',
                $opcoes
            );

            // Atualiza o status do contato
            $contato->status = 'proposta_enviada';
            $contato->save();

            // Registra log
            if ($request->call_id) {
                ScriptLog::create([
                    'script_id' => $opcoes['script_id'],
                    'contato_id' => $contato->id,
                    'empresa_id' => $contato->empresa_id,
                    'mailing_id' => $contato->mailing_id,
                    'call_id' => $request->call_id,
                    'tipo_evento' => 'proposta_pagamento_gerada',
                    'dados' => [
                        'proposta_id' => $proposta->id,
                        'valor_proposta' => $valorProposta,
                        'desconto' => $descontoPercentual,
                        'sms_status' => $proposta->sms_status,
                    ],
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Proposta de pagamento gerada e SMS enviado com sucesso!',
                'data' => [
                    'proposta_id' => $proposta->id,
                    'uuid' => $proposta->uuid,
                    'valor_proposta' => $valorProposta,
                    'desconto' => $descontoPercentual,
                    'link_pagamento' => $proposta->link_pagamento,
                    'sms_enviado' => in_array($proposta->sms_status, ['enviado', 'entregue']),
                    'mensagem_para_ia' => $this->gerarMensagemParaIA($proposta, $descontoPercentual),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao gerar proposta de pagamento: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro ao gerar proposta de pagamento: ' . $e->getMessage(),
                'mensagem_para_ia' => 'Desculpe, houve um erro ao gerar a proposta de pagamento. Por favor, tente novamente.',
            ], 500);
        }
    }

    /**
     * Custom LLM Function: Registrar agendamento de pagamento
     * POST /api/intencoes/agendar-pagamento
     */
    public function agendarPagamento(Request $request)
    {
        try {
            Log::info('Agendando pagamento', $request->all());

            // Validação
            $validator = Validator::make($request->all(), [
                'contato_id' => 'required|integer|exists:contatos,id',
                'data_agendamento' => 'required|date|after:now',
                'call_id' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $contato = Contato::findOrFail($request->contato_id);

            // Atualiza o contato
            $contato->status = 'pagamento_agendado';
            $contato->save();

            // Registra log
            if ($request->call_id) {
                ScriptLog::create([
                    'script_id' => $contato->mailing->script_id ?? null,
                    'contato_id' => $contato->id,
                    'empresa_id' => $contato->empresa_id,
                    'mailing_id' => $contato->mailing_id,
                    'call_id' => $request->call_id,
                    'tipo_evento' => 'pagamento_agendado',
                    'dados' => [
                        'data_agendamento' => $request->data_agendamento,
                    ],
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Pagamento agendado com sucesso',
                'data' => [
                    'data_agendamento' => $request->data_agendamento,
                    'mensagem_para_ia' => "Perfeito! Agendei o pagamento para " . date('d/m/Y', strtotime($request->data_agendamento)) . ". Enviarei um lembrete próximo dessa data.",
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao agendar pagamento: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Erro ao agendar pagamento',
            ], 500);
        }
    }

    /**
     * Custom LLM Function: Registrar interesse em negociar
     * POST /api/intencoes/interesse-negociar
     */
    public function interesseNegociar(Request $request)
    {
        try {
            Log::info('Interesse em negociar detectado', $request->all());

            $validator = Validator::make($request->all(), [
                'contato_id' => 'required|integer|exists:contatos,id',
                'call_id' => 'nullable|string',
                'observacao' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $contato = Contato::findOrFail($request->contato_id);
            $contato->status = 'interessado_negociar';
            $contato->save();

            if ($request->call_id) {
                ScriptLog::create([
                    'script_id' => $contato->mailing->script_id ?? null,
                    'contato_id' => $contato->id,
                    'empresa_id' => $contato->empresa_id,
                    'mailing_id' => $contato->mailing_id,
                    'call_id' => $request->call_id,
                    'tipo_evento' => 'interesse_negociar',
                    'dados' => [
                        'observacao' => $request->observacao,
                    ],
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Interesse registrado com sucesso',
                'data' => [
                    'mensagem_para_ia' => "Ótimo! Vou registrar seu interesse. Podemos fazer uma proposta de pagamento agora mesmo!",
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao registrar interesse: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Erro ao registrar interesse',
            ], 500);
        }
    }

    /**
     * Processa intenção baseado no tipo
     */
    private function processarIntencaoPorTipo(string $intencao, ?Contato $contato, array $contexto): array
    {
        switch (strtolower($intencao)) {
            case 'aceita_pagar':
            case 'interesse_pagamento':
                return [
                    'mensagem' => 'Cliente demonstrou interesse em pagar',
                    'data' => ['status' => 'interessado'],
                ];

            case 'recusa':
            case 'nao_pode_pagar':
                if ($contato) {
                    $contato->status = 'recusou';
                    $contato->save();
                }
                return [
                    'mensagem' => 'Cliente recusou a proposta',
                    'data' => ['status' => 'recusado'],
                ];

            default:
                return [
                    'mensagem' => 'Intenção registrada',
                    'data' => ['intencao' => $intencao],
                ];
        }
    }

    /**
     * Registra log da detecção de intenção
     */
    private function registrarLog(?string $callId, string $intencao, array $contexto, ?Contato $contato): void
    {
        if (!$callId || !$contato) {
            return;
        }

        ScriptLog::create([
            'script_id' => $contato->mailing->script_id ?? null,
            'contato_id' => $contato->id,
            'empresa_id' => $contato->empresa_id,
            'mailing_id' => $contato->mailing_id,
            'call_id' => $callId,
            'tipo_evento' => 'intencao_detectada',
            'dados' => [
                'intencao' => $intencao,
                'contexto' => $contexto,
            ],
        ]);
    }

    /**
     * Gera mensagem amigável para a IA comunicar ao cliente
     */
    private function gerarMensagemParaIA($proposta, float $descontoPercentual): string
    {
        $valorFormatado = number_format($proposta->valor_proposta, 2, ',', '.');

        $mensagem = "Perfeito! ";

        if ($descontoPercentual > 0) {
            $mensagem .= "Consegui aprovar um desconto de " . number_format($descontoPercentual, 0) . "% para você! ";
        }

        $mensagem .= "O valor para pagamento é R$ {$valorFormatado}. ";
        $mensagem .= "Acabei de enviar um SMS para o seu telefone com o link para realizar o pagamento. ";
        $mensagem .= "Você pode pagar agora mesmo e já resolver isso. O link é válido por 7 dias. ";
        $mensagem .= "Posso ajudar em mais alguma coisa?";

        return $mensagem;
    }
}
