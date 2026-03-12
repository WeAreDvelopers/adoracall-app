<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Services\PagamentoService;
use App\Models\PropostaPagamento;
use App\Models\Contato;

class PagamentoController extends Controller
{
    private PagamentoService $pagamentoService;

    public function __construct(PagamentoService $pagamentoService)
    {
        $this->pagamentoService = $pagamentoService;
    }

    /**
     * Cria uma proposta de pagamento e envia via SMS
     * POST /api/propostas-pagamento
     */
    public function criar(Request $request)
    {
        try {
            // Validação
            $validator = Validator::make($request->all(), [
                'contato_id' => 'required|integer|exists:contatos,id',
                'valor_proposta' => 'required|numeric|min:0.01',
                'tipo_proposta' => 'nullable|in:pix,boleto,cartao,link_generico',
                'valor_original' => 'nullable|numeric|min:0',
                'script_id' => 'nullable|integer|exists:scripts,id',
                'mensagem_customizada' => 'nullable|string|max:1000',
                'valido_ate' => 'nullable|date|after:now',
                'metadata' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Busca o contato
            $contato = Contato::findOrFail($request->contato_id);

            // Prepara opções
            $opcoes = [
                'valor_original' => $request->valor_original,
                'script_id' => $request->script_id,
                'mensagem_customizada' => $request->mensagem_customizada,
                'valido_ate' => $request->valido_ate,
                'metadata' => $request->metadata,
                'criado_por' => $request->user()->name ?? 'api',
            ];

            // Cria e envia a proposta
            $proposta = $this->pagamentoService->criarEEnviarProposta(
                $contato,
                $request->valor_proposta,
                $request->tipo_proposta ?? 'link_generico',
                $opcoes
            );

            return response()->json([
                'success' => true,
                'message' => 'Proposta criada e SMS enviado com sucesso',
                'proposta' => $proposta
            ], 201);

        } catch (\Exception $e) {
            Log::error('Erro ao criar proposta de pagamento: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro ao criar proposta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lista propostas de pagamento com filtros
     * GET /api/propostas-pagamento
     */
    public function listar(Request $request)
    {
        try {
            $query = PropostaPagamento::with(['contato', 'script', 'mailing']);

            // Filtros
            if ($request->has('contato_id')) {
                $query->where('contato_id', $request->contato_id);
            }

            if ($request->has('mailing_id')) {
                $query->where('mailing_id', $request->mailing_id);
            }

            if ($request->has('script_id')) {
                $query->where('script_id', $request->script_id);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('tipo_proposta')) {
                $query->where('tipo_proposta', $request->tipo_proposta);
            }

            if ($request->has('telefone')) {
                $query->where('telefone', 'LIKE', '%' . $request->telefone . '%');
            }

            if ($request->has('data_inicio') && $request->has('data_fim')) {
                $query->whereBetween('created_at', [
                    $request->data_inicio,
                    $request->data_fim
                ]);
            }

            // Ordenação
            $orderBy = $request->get('order_by', 'created_at');
            $orderDir = $request->get('order_dir', 'desc');
            $query->orderBy($orderBy, $orderDir);

            // Paginação
            $perPage = $request->get('per_page', 15);
            $propostas = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'propostas' => $propostas
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao listar propostas: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Erro ao listar propostas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostra detalhes de uma proposta
     * GET /api/propostas-pagamento/{id}
     */
    public function mostrar($id)
    {
        try {
            $proposta = PropostaPagamento::with(['contato', 'script', 'mailing'])
                ->findOrFail($id);

            // Verifica se está expirada
            $proposta->verificarExpiracao();

            return response()->json([
                'success' => true,
                'proposta' => $proposta
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao buscar proposta: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Proposta não encontrada'
            ], 404);
        }
    }

    /**
     * Reenviar SMS de uma proposta
     * POST /api/propostas-pagamento/{id}/reenviar-sms
     */
    public function reenviarSms($id, Request $request)
    {
        try {
            $proposta = PropostaPagamento::findOrFail($id);

            // Verifica se não está expirada
            if ($proposta->estaExpirada()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Proposta expirada. Crie uma nova proposta.'
                ], 400);
            }

            $mensagemCustomizada = $request->get('mensagem_customizada');
            $sucesso = $this->pagamentoService->enviarSmsProposta($proposta, $mensagemCustomizada);

            if ($sucesso) {
                return response()->json([
                    'success' => true,
                    'message' => 'SMS reenviado com sucesso',
                    'proposta' => $proposta->fresh()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Erro ao reenviar SMS'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Erro ao reenviar SMS: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Erro ao reenviar SMS: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancelar uma proposta
     * POST /api/propostas-pagamento/{id}/cancelar
     */
    public function cancelar($id)
    {
        try {
            $proposta = PropostaPagamento::findOrFail($id);

            if (in_array($proposta->status, ['pago', 'cancelado'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Não é possível cancelar uma proposta ' . $proposta->status
                ], 400);
            }

            $proposta->status = 'cancelado';
            $proposta->save();

            return response()->json([
                'success' => true,
                'message' => 'Proposta cancelada com sucesso',
                'proposta' => $proposta
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao cancelar proposta: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Erro ao cancelar proposta'
            ], 500);
        }
    }

    /**
     * Webhook do Twilio para status de SMS
     * POST /api/webhooks/twilio/sms-status
     */
    public function webhookTwilioSmsStatus(Request $request)
    {
        try {

            $messageSid = $request->get('MessageSid');
            $status = $request->get('MessageStatus');
            $errorMessage = $request->get('ErrorMessage');

            if (!$messageSid || !$status) {
                return response()->json([
                    'success' => false,
                    'error' => 'MessageSid e MessageStatus são obrigatórios'
                ], 400);
            }

            $sucesso = $this->pagamentoService->atualizarStatusSms(
                $messageSid,
                $status,
                $errorMessage
            );

            if ($sucesso) {
                return response()->json([
                    'success' => true,
                    'message' => 'Status atualizado com sucesso'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Proposta não encontrada para este MessageSid'
                ], 404);
            }

        } catch (\Exception $e) {
            Log::error('Erro no webhook Twilio: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Erro ao processar webhook'
            ], 500);
        }
    }

    /**
     * Webhook de confirmação de pagamento (para gateway futuro)
     * POST /api/webhooks/pagamento/confirmacao
     */
    public function webhookPagamentoConfirmacao(Request $request)
    {
        try {

            // Validação básica
            $validator = Validator::make($request->all(), [
                'proposta_uuid' => 'required|string|exists:propostas_pagamento,uuid',
                'valor_pago' => 'required|numeric|min:0',
                'transaction_id' => 'nullable|string',
                'status' => 'required|in:aprovado,pendente,recusado',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $proposta = PropostaPagamento::where('uuid', $request->proposta_uuid)->firstOrFail();

            if ($request->status === 'aprovado') {
                $this->pagamentoService->confirmarPagamento(
                    $proposta,
                    $request->valor_pago,
                    $request->transaction_id,
                    $request->all()
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Pagamento confirmado com sucesso'
                ]);
            } else {
                return response()->json([
                    'success' => true,
                    'message' => 'Status de pagamento registrado: ' . $request->status
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Erro no webhook de pagamento: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Erro ao processar webhook de pagamento'
            ], 500);
        }
    }

    /**
     * Estatísticas de propostas
     * GET /api/propostas-pagamento/estatisticas
     */
    public function estatisticas(Request $request)
    {
        try {
            $mailingId = $request->get('mailing_id');
            $periodo = null;

            if ($request->has('data_inicio') && $request->has('data_fim')) {
                $periodo = [
                    'inicio' => $request->data_inicio,
                    'fim' => $request->data_fim,
                ];
            }

            $stats = $this->pagamentoService->obterEstatisticas($mailingId, $periodo);

            return response()->json([
                'success' => true,
                'estatisticas' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao gerar estatísticas: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Erro ao gerar estatísticas'
            ], 500);
        }
    }

    /**
     * Processa propostas expiradas
     * POST /api/propostas-pagamento/processar-expiradas
     */
    public function processarExpiradas()
    {
        try {
            $count = $this->pagamentoService->processarPropostasExpiradas();

            return response()->json([
                'success' => true,
                'message' => "Processadas {$count} propostas expiradas"
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao processar expiradas: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Erro ao processar propostas expiradas'
            ], 500);
        }
    }

    /**
     * Busca proposta por UUID (para página de pagamento)
     * GET /api/propostas-pagamento/uuid/{uuid}
     */
    public function buscarPorUuid($uuid)
    {
        try {
            $proposta = PropostaPagamento::where('uuid', $uuid)
                ->with(['contato'])
                ->firstOrFail();

            // Verifica se está expirada
            if ($proposta->estaExpirada()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Proposta expirada',
                    'proposta' => $proposta
                ], 410); // 410 Gone
            }

            return response()->json([
                'success' => true,
                'proposta' => $proposta
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Proposta não encontrada'
            ], 404);
        }
    }
}
