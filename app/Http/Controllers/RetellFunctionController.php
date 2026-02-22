<?php
namespace App\Http\Controllers;

use App\Http\Controllers\WebhookController;
use App\Models\Acordo;
use App\Models\Contato;
use App\Models\Ligacao;
use App\Models\Proposta;
use App\Services\ProposalService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class RetellFunctionController extends Controller
{
    protected $proposalService;

    public function __construct(ProposalService $proposalService)
    {
        $this->proposalService = $proposalService;
    }

    /**
     * Function 1: Buscar propostas do cliente
     * Chamada pelo Retell quando agente decide oferecer opções
     */
    public function getPropostas(Request $request)
    {
        try {
            Log::info('🔄 [Retell Function] getPropostas chamado', $request->all());

            // Tentar obter contato_id de diferentes fontes
            $contato_id = $request->input('contato_id') ??
            $request->input('customer_id') ??
            $request->input('call_metadata.contato_id') ??
            $request->input('metadata.contato_id');

            if (! $contato_id) {
                Log::error('❌ contato_id não encontrado na requisição');
                return response()->json([
                    'success'     => false,
                    'error'       => 'Contato não identificado',
                    'codigo_erro' => 'CONTATO_NAO_IDENTIFICADO',
                ], 400);
            }

            $contato = Contato::find($contato_id);

            if (! $contato) {
                Log::error("❌ Contato {$contato_id} não encontrado");
                return response()->json([
                    'success'     => false,
                    'error'       => 'Contato não encontrado no sistema',
                    'codigo_erro' => 'CONTATO_NAO_ENCONTRADO',
                ], 404);
            }

            Log::info("✅ Contato encontrado: {$contato->nome_completo} (ID: {$contato->id})");

            // Verificar se já existem propostas ativas
            $propostas_existentes = Proposta::where('contato_id', $contato_id)
                ->where('status', 'ativa')
                ->where(function ($query) {
                    $query->whereNull('expira_em')
                        ->orWhere('expira_em', '>', Carbon::now());
                })
                ->get();

            // Se não houver propostas válidas, gerar novas
            if ($propostas_existentes->isEmpty()) {
                Log::info("📊 Gerando novas propostas para {$contato->nome_completo}");
                $propostas_data = $this->proposalService->gerarPropostasParaCliente($contato);
            } else {
                Log::info("♻️ Usando propostas existentes para {$contato->nome_completo}");
                $propostas_data = $propostas_existentes->toArray();
            }

            // Formatar para o agente
            $propostas_formatadas = array_map(function ($p, $index) {
                return [
                    'numero'      => $index + 1,
                    'descricao'   => $p['descricao_agente'] ?? $p['descricao_agente'],
                    'tipo'        => $p['tipo'] ?? 'parcelamento',
                    'valor_final' => $p['valor_final'] ?? 0,
                ];
            }, $propostas_data, array_keys($propostas_data));

            Log::info('✅ ' . count($propostas_formatadas) . ' propostas formatadas para o agente');

            return response()->json([
                'success'            => true,
                'contato_nome'       => $contato->primeiro_nome,
                'contato_id'         => $contato->id,
                'valor_total_devido' => number_format($contato->valor_debito, 2, ',', '.'),
                'propostas'          => $propostas_formatadas,
                'mensagem_agente'    => 'Temos ' . count($propostas_formatadas) . ' opções disponíveis para você',
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erro em getPropostas: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'success'     => false,
                'error'       => 'Erro ao processar propostas: ' . $e->getMessage(),
                'codigo_erro' => 'ERRO_INTERNO',
            ], 500);
        }
    }

    /**
     * Function 2: Aceitar proposta
     * Chamada quando cliente confirma verbalmente que aceita
     */
    public function aceitarProposta(Request $request)
    {
        try {
            Log::info('🎯 [Retell Function] aceitarProposta chamado', $request->all());

            $contato_id        = $request->input('contato_id');
            $ligacao_id        = $request->input('ligacao_id');
            $proposta_tipo     = $request->input('proposta_tipo');
            $confirmacao_texto = $request->input('confirmacao_texto');
            $transcricao       = $request->input('transcricao');

            // Validação
            if (! $contato_id || ! $proposta_tipo || ! $confirmacao_texto) {
                Log::warning('⚠️ Dados incompletos para aceitar proposta');
                return response()->json([
                    'success'             => false,
                    'error'               => 'Dados incompletos',
                    'campos_obrigatorios' => ['contato_id', 'proposta_tipo', 'confirmacao_texto'],
                ], 400);
            }

            $contato = Contato::find($contato_id);
            $ligacao = $ligacao_id ? Ligacao::find($ligacao_id) : null;

            if (! $contato) {
                Log::error("❌ Contato {$contato_id} não encontrado");
                return response()->json([
                    'success' => false,
                    'error'   => 'Contato não encontrado',
                ], 404);
            }

            // Buscar última proposta ativa deste tipo
            $proposta = Proposta::where('contato_id', $contato_id)
                ->where('tipo', $proposta_tipo)
                ->where('status', 'ativa')
                ->where(function ($query) {
                    $query->whereNull('expira_em')
                        ->orWhere('expira_em', '>', Carbon::now());
                })
                ->latest()
                ->first();

            if (! $proposta) {
                Log::error("❌ Proposta do tipo {$proposta_tipo} não encontrada");
                return response()->json([
                    'success' => false,
                    'error'   => 'Proposta não encontrada ou expirada',
                ], 404);
            }

            // Criar acordo
            $acordo = Acordo::create([
                'contato_id'             => $contato_id,
                'ligacao_id'             => $ligacao_id,
                'proposta_id'            => $proposta->id,
                'aceito_em'              => Carbon::now(),
                'confirmacao_texto'      => $confirmacao_texto,
                'transcricao_trecho'     => $transcricao,
                'valor_acordado'         => $proposta->valor_final,
                'parcelas_acordadas'     => $proposta->parcelas,
                'valor_parcela_acordada' => $proposta->valor_parcela,
                'status'                 => 'pendente_pagamento',
                'user_agent'             => $request->header('User-Agent'),
                'ip_address'             => $request->ip(),
            ]);

            // Gerar link de pagamento
            $link_pagamento = $this->gerarLinkPagamento($acordo);
            $acordo->update(['link_pagamento' => $link_pagamento]);

            // Marcar proposta como aceita
            $proposta->update(['status' => 'aceita']);

            // Atualizar status do contato
            $contato->update(['status' => 'acordo_realizado']);

            Log::info("✅ Acordo criado com sucesso. ID: {$acordo->id}");

            // Enviar confirmação (você pode integrar com seu gateway de pagamento aqui)
            $this->enviarConfirmacaoPagamento($contato, $acordo);

            return response()->json([
                'success'          => true,
                'acordo_id'        => $acordo->id,
                'mensagem_cliente' => 'Perfeito! Vamos enviar um email com o link de pagamento. Obrigado!',
                'link_pagamento'   => $link_pagamento,
                'valor_acordado'   => number_format($acordo->valor_acordado, 2, ',', '.'),
                'parcelas'         => $acordo->parcelas_acordadas,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erro em aceitarProposta: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'error'   => 'Erro ao processar acordo: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Gerar link de pagamento
     * TODO: Integrar com seu gateway de pagamento (MercadoPago, Stripe, etc)
     */
    private function gerarLinkPagamento(Acordo $acordo): string
    {
        $base_url = config('app.payment_base_url', url('/pagamento'));
        $link     = $base_url . '/' . $acordo->id;

        Log::info("💳 Link de pagamento gerado: {$link}");

        return $link;
    }

    /**
     * Enviar confirmação ao cliente
     * TODO: Implementar envio de email/SMS
     */
    private function enviarConfirmacaoPagamento(Contato $contato, Acordo $acordo): void
    {
        Log::info("📧 Enviando confirmação para {$contato->telefone}");

        // TODO: Integrar com Twilio SMS ou serviço de email
        // Por enquanto, apenas logamos
    }

    /**
     * Webhook: Retell notifica fim de chamada
     * POST /api/retell/webhook
     *
     * ⚠️ DEPRECATED: Use WebhookController@handleRetellWebhook em vez deste
     * Este método agora apenas redireciona para o controlador principal
     */
    public function webhookLigacao(Request $request)
    {
        Log::warning("⚠️ [DEPRECATED] RetellFunctionController@webhookLigacao está sendo chamado");
        Log::info("📢 Redirecionando para WebhookController@handleRetellWebhook");

        // Redirecionar para o controlador principal que tem a lógica inteligente
        $webhookController = new WebhookController();
        return $webhookController->handleRetellWebhook($request);
    }
}
