<?php

namespace App\Http\Controllers;

use App\Services\IntegracaoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Ligacao;
use App\Models\Contato;
use App\Models\QueueJob;
use Carbon\Carbon;

/**
 * ⚠️ IMPORTANTE - CONFIGURAÇÃO NO RETELL
 *
 * A URL do webhook deve ser configurada no console do Retell como:
 * {APP_URL}/api/ura/webhook
 *
 * Exemplo: https://seu-dominio.com/api/ura/webhook
 *
 * Este webhook é PÚBLICO e não requer autenticação JWT.
 * Recebe eventos de chamada do Retell e implementa lógica inteligente de retry.
 */
class WebhookController extends Controller
{
    /**
     * ENDPOINT PRINCIPAL: Recebe webhook do Retell AI
     * POST /api/ura/webhook
     *
     * Fluxo de Processamento:
     * 1. Valida assinatura (opcional)
     * 2. Extrai call_id e status da chamada
     * 3. Atualiza registros de Ligacao
     * 4. Analisa resultado e implementa retry inteligente:
     *    - NÃO RESPONDIDA → Agendar retry simples
     *    - RESPONDIDA + SEM TRATATIVA → Agendar retry simples
     *    - RESPONDIDA + COM ACORDO → Marcar como COMPLETADO
     *    - ERRO TÉCNICO → Retry com exponential backoff
     * 5. Atualiza QueueJob com decisão
     * 6. Atualiza estatísticas do Mailing
     */
    public function handleRetellWebhook(Request $request)
    {
        // Log de entrada
        $requestId = uniqid('webhook_');
        Log::info("📥 [WEBHOOK-RECEIVED] ID: {$requestId} | Time: " . date('Y-m-d H:i:s'));
        Log::debug("📥 [WEBHOOK-HEADERS] " . json_encode($request->headers->all()));
        Log::debug("📥 [WEBHOOK-BODY] " . json_encode($request->all()));

        try {
            // Validar assinatura (opcional - se Retell envia)
            $this->validateWebhookSignature($request);

            $data = $request->all();

            // Extrair dados importantes
            $callId = $data['call_id'] ?? null;
            $status = $data['call_status'] ?? null;
            $metadata = $data['metadata'] ?? [];
            $contatoId = $metadata['contato_id'] ?? null;

            Log::info("✅ [WEBHOOK-PARSED] Call ID: {$callId} | Status: {$status} | Contato: {$contatoId}");

            // Buscar ligação existente (sem Global Scope - webhook é público)
            $ligacao = Ligacao::withoutGlobalScopes()->where('call_id_retell', $callId)->first();

            if (!$ligacao) {
                Log::warning("⚠️  [WEBHOOK-NOT-FOUND] Call ID {$callId} não encontrado no BD");

                // Criar nova ligação se não existir
                if ($contatoId) {
                    // Buscar empresa_id do metadata ou do contato
                    $empresaId = $metadata['empresa_id'] ?? null;
                    if (!$empresaId) {
                        $contato = Contato::withoutGlobalScopes()->find($contatoId);
                        $empresaId = $contato->empresa_id ?? null;
                    }

                    $ligacao = Ligacao::withoutGlobalScopes()->create([
                        'contato_id' => $contatoId,
                        'empresa_id' => $empresaId,
                        'call_id_retell' => $callId,
                        'status' => $status ?? 'initiated',
                        'detalhes' => [
                            'webhook_source' => 'retell',
                            'received_at' => Carbon::now()->toIso8601String(),
                            'metadata' => $metadata,
                        ]
                    ]);

                    Log::info("✅ [WEBHOOK-CREATED] Nova ligação criada: ID {$ligacao->id}");
                }
            } else {
                // Atualizar ligação existente
                $oldStatus = $ligacao->status;
                $ligacao->status = $status ?? $oldStatus;

                // Atualizar detalhes
                $detalhes = $ligacao->detalhes ?? [];
                if (!isset($detalhes['webhook_events'])) {
                    $detalhes['webhook_events'] = [];
                }

                $detalhes['last_webhook'] = Carbon::now()->toIso8601String();
                $detalhes['last_status'] = $status;
                $detalhes['webhook_events'][] = [
                    'timestamp' => Carbon::now()->toIso8601String(),
                    'status' => $status,
                    'request_id' => $requestId,
                ];

                $ligacao->detalhes = $detalhes;
                $ligacao->save();

                Log::info("✅ [WEBHOOK-UPDATED] Ligação {$ligacao->id} atualizada: {$oldStatus} → {$status}");
            }

            // Processar por tipo de evento
            $this->processWebhookEvent($status, $ligacao, $data);

            // Log de sucesso
            Log::info("✅ [WEBHOOK-PROCESSED] Webhook processado com sucesso: {$requestId}");

            return response()->json([
                'success' => true,
                'message' => 'Webhook processado com sucesso',
                'webhook_id' => $requestId,
                'ligacao_id' => $ligacao->id ?? null,
            ]);

        } catch (\Exception $e) {
            Log::error("❌ [WEBHOOK-ERROR] {$requestId}: " . $e->getMessage());
            Log::error("❌ [WEBHOOK-TRACE] " . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Processa eventos específicos do webhook
     */
    private function processWebhookEvent($status, $ligacao, $data)
    {
        $contato = $ligacao->contato;

        switch ($status) {
            case 'initiated':
                Log::info("🔄 [WEBHOOK-EVENT] Ligação iniciada: {$ligacao->id}");
                if ($contato) {
                    Log::info("🔄 [WEBHOOK-EVENT-DETAIL] Contato: {$contato->nome} | Tel: {$contato->telefone}");
                }
                break;

            case 'ongoing':
                Log::info("📞 [WEBHOOK-EVENT] Ligação em andamento: {$ligacao->id}");
                break;

            case 'completed':
                Log::info("✅ [WEBHOOK-EVENT] Ligação completada: {$ligacao->id}");
                $this->handleCallCompleted($ligacao, $data);
                break;

            case 'failed':
                Log::error("❌ [WEBHOOK-EVENT] Ligação falhou: {$ligacao->id}");
                $this->handleCallFailed($ligacao, $data);
                break;

            case 'timeout':
                Log::warning("⏰ [WEBHOOK-EVENT] Ligação expirou (timeout): {$ligacao->id}");
                // Timeout = não atendeu, agendar retry
                $this->handleCallTimeout($ligacao, $data);
                break;

            default:
                Log::info("❓ [WEBHOOK-EVENT] Status desconhecido: {$status}");
        }
    }

    /**
     * Handle quando ligação completa com sucesso
     * Importante: Aqui decidimos se foi sucesso real (com tratativa) ou só completou a ligação
     */
    private function handleCallCompleted($ligacao, $data)
    {
        $contato = $ligacao->contato;
        $queueJob = null;

        if ($contato) {
            // Tentar encontrar QueueJob associado
            $queueJob = QueueJob::withoutGlobalScopes()
                ->where('contato_id', $contato->id)
                ->where('mailing_id', $contato->mailing_id)
                ->where('status', 'awaiting_callback')
                ->first();

            if (!$queueJob) {
                // Tenta encontrar sem status específico (fallback)
                $queueJob = QueueJob::withoutGlobalScopes()
                    ->where('contato_id', $contato->id)
                    ->where('mailing_id', $contato->mailing_id)
                    ->latest()
                    ->first();
            }

            // Extrair informações da chamada
            $duracao = $data['duration'] ?? $data['call_duration'] ?? 0;
            $transcricao = $data['transcription'] ?? $data['transcript'] ?? null;
            $resultado = $data['resultado'] ?? 'completada';
            $conectada = $data['call_answered'] ?? ($duracao > 0); // Se teve duração, foi respondida
            $aceitouProposta = $data['proposta_aceita'] ?? false; // Flag se usuário aceitou proposta

            Log::info("📊 [CALL-COMPLETED] Contato: {$contato->nome} | Duração: {$duracao}s | Respondida: " . ($conectada ? 'Sim' : 'Não'));

            if ($transcricao) {
                Log::info("📝 [CALL-TRANSCRIPTION] " . substr($transcricao, 0, 150) . "...");
            }

            // ==== LÓGICA INTELIGENTE DE DECISÃO ====
            $houveTratativa = false;
            $proximaAcao = 'pending'; // Por padrão, agenda retry

            if (!$conectada) {
                // Ligação não foi atendida
                Log::warning("📞 [CALL-DECISION] Ligação não atendida - será reagendada para retry");
                $contato->status = 'nao_atendida';
                $contato->resultado = 'nao_atendida';
                $proximaAcao = 'pending';
                $houveTratativa = false;

            } elseif ($duracao < 10) {
                // Ligação foi atendida mas duração muito curta (provável desligamento sem conversa)
                Log::warning("📞 [CALL-DECISION] Atendida mas duração curta ({$duracao}s) - será reagendada para retry");
                $contato->status = 'desligou_rapido';
                $contato->resultado = 'desligou_rapido';
                $proximaAcao = 'pending';
                $houveTratativa = false;

            } elseif ($aceitouProposta) {
                // Usuário aceitou proposta - SUCESSO FINAL!
                Log::info("✅ [CALL-DECISION] Usuário aceitou proposta - tratativa concluída!");
                $contato->status = 'acordo_realizado';
                $contato->resultado = 'acordo_realizado';
                $proximaAcao = 'completed';
                $houveTratativa = true;

            } else {
                // Ligação completou, teve conversa decente, mas sem acordo final
                // Isso pode significar: usuário recusou, quer pensar, etc
                Log::warning("📞 [CALL-DECISION] Ligação completada mas sem decisão final - será reagendada para retry");
                $contato->status = 'sem_tratativa';
                $contato->resultado = 'sem_tratativa';
                $proximaAcao = 'pending';
                $houveTratativa = false;
            }

            // Atualizar timestamp e salvar contato
            $contato->ultima_ligacao = Carbon::now();
            $contato->save();

            Log::info("✅ [CONTATO-UPDATED] Contato {$contato->id} ({$contato->nome}) → {$contato->status}");

            // ==== ATUALIZAR QUEUE JOB BASEADO NA DECISÃO ====
            if ($queueJob) {
                $this->atualizarQueueJobComResultado($queueJob, $proximaAcao, [
                    'chamada_respondida' => $conectada,
                    'duracao_segundos' => $duracao,
                    'houve_tratativa' => $houveTratativa,
                    'aceitou_proposta' => $aceitouProposta,
                    'transcricao' => $transcricao ? substr($transcricao, 0, 500) : null,
                ]);
            }
        }
    }

    /**
     * Handle quando ligação falha (erro técnico)
     */
    private function handleCallFailed($ligacao, $data)
    {
        $contato = $ligacao->contato;
        $queueJob = null;

        if ($contato) {
            $motivo = $data['failure_reason'] ?? $data['disconnection_reason'] ?? 'desconhecido';

            Log::error("❌ [CALL-FAILED] Contato: {$contato->nome} | Motivo: {$motivo}");

            // Tentar encontrar QueueJob associado (sem Global Scope - webhook é público)
            $queueJob = QueueJob::withoutGlobalScopes()
                ->where('contato_id', $contato->id)
                ->where('mailing_id', $contato->mailing_id)
                ->where('status', 'awaiting_callback')
                ->first();

            if (!$queueJob) {
                $queueJob = QueueJob::withoutGlobalScopes()
                    ->where('contato_id', $contato->id)
                    ->where('mailing_id', $contato->mailing_id)
                    ->latest()
                    ->first();
            }

            // Erro técnico deve usar retry exponencial
            Log::warning("🔄 [CALL-DECISION] Erro técnico - agendando retry com backoff exponencial");
            $contato->status = 'erro_ligacao';
            $contato->resultado = 'erro_' . $motivo;
            $contato->save();

            // Atualizar QueueJob com retry exponencial
            if ($queueJob) {
                $this->atualizarQueueJobComRetryExponencial($queueJob, $motivo);
            }
        }
    }

    /**
     * Handle quando ligação expira sem resposta (timeout)
     */
    private function handleCallTimeout($ligacao, $data)
    {
        $contato = $ligacao->contato;
        $queueJob = null;

        if ($contato) {
            Log::warning("⏰ [CALL-TIMEOUT] Contato: {$contato->nome} - Timeout/Não atendeu");

            // Tentar encontrar QueueJob (sem Global Scope - webhook é público)
            $queueJob = QueueJob::withoutGlobalScopes()
                ->where('contato_id', $contato->id)
                ->where('mailing_id', $contato->mailing_id)
                ->where('status', 'awaiting_callback')
                ->first();

            if (!$queueJob) {
                $queueJob = QueueJob::withoutGlobalScopes()
                    ->where('contato_id', $contato->id)
                    ->where('mailing_id', $contato->mailing_id)
                    ->latest()
                    ->first();
            }

            $contato->status = 'nao_atendida';
            $contato->resultado = 'timeout';
            $contato->save();

            // Timeout = ninguém respondeu, agendar retry simples
            Log::warning("🔄 [CALL-DECISION] Timeout - agendando retry");
            if ($queueJob) {
                $this->atualizarQueueJobComResultado($queueJob, 'pending', [
                    'chamada_respondida' => false,
                    'duracao_segundos' => 0,
                    'houve_tratativa' => false,
                    'motivo_timeout' => $data['disconnection_reason'] ?? 'unknown',
                ]);
            }
        }
    }

    /**
     * Atualiza QueueJob com resultado da chamada
     * Marca como 'completed' se houve sucesso com tratativa, ou 'pending' para retry simples
     */
    private function atualizarQueueJobComResultado(QueueJob $queueJob, string $proximaAcao, array $detalhes)
    {
        $mailing = $queueJob->mailing;
        $maxTentativas = $mailing->max_tentativas ?? $mailing->script->tentativas_max ?? 3;

        if ($proximaAcao === 'completed') {
            // ✅ SUCESSO FINAL - Usuário forneceu tratativa (acordo)
            Log::info("✅ [QUEUE-UPDATE] QueueJob {$queueJob->id} COMPLETADO - Tratativa realizada");
            $queueJob->status = 'completed';
            $queueJob->resultado = array_merge($queueJob->resultado ?? [], $detalhes);
            $queueJob->completed_at = Carbon::now();

        } else {
            // 🔄 RETRY NECESSÁRIO - Sem tratativa ou não atendeu
            $queueJob->tentativas += 1;

            if ($queueJob->tentativas >= $maxTentativas) {
                // Atingiu limite de tentativas
                Log::error("❌ [QUEUE-UPDATE] QueueJob {$queueJob->id} FALHOU PERMANENTEMENTE - {$queueJob->tentativas}/{$maxTentativas} tentativas");
                $queueJob->status = 'failed';
                $queueJob->resultado = array_merge($queueJob->resultado ?? [], array_merge($detalhes, [
                    'razao_falha' => 'max_tentativas_atingido'
                ]));

            } else {
                // Agendar retry
                $intervaloMinutos = intval(env('RETRY_INTERVAL_MINUTES', 5));
                $proximaTentativa = Carbon::now()->addMinutes($intervaloMinutos);

                Log::info("🔄 [QUEUE-UPDATE] QueueJob {$queueJob->id} RETRY AGENDADO - Tentativa {$queueJob->tentativas}/{$maxTentativas}");
                Log::info("⏱️  Próxima tentativa em {$intervaloMinutos} minutos (" . $proximaTentativa->format('Y-m-d H:i:s') . ")");

                $queueJob->status = 'pending';
                $queueJob->proxima_tentativa = $proximaTentativa;
                $queueJob->resultado = array_merge($queueJob->resultado ?? [], array_merge($detalhes, [
                    'ultimo_retry_agendado_em' => Carbon::now()->toIso8601String(),
                    'intervalo_retry_minutos' => $intervaloMinutos
                ]));
            }
        }

        $queueJob->save();
        Log::info("💾 [QUEUE-SAVED] QueueJob {$queueJob->id} salvo no banco de dados");

        // Atualizar estatísticas do mailing
        if ($mailing) {
            $mailing->atualizarEstatisticas();
            Log::info("📊 [MAILING-STATS-UPDATED] Estatísticas do mailing {$mailing->id} atualizadas");
        }
    }

    /**
     * Atualiza QueueJob com retry exponencial para erros técnicos
     */
    private function atualizarQueueJobComRetryExponencial(QueueJob $queueJob, string $motivo)
    {
        $mailing = $queueJob->mailing;
        $maxTentativas = $mailing->max_tentativas ?? $mailing->script->tentativas_max ?? 3;

        $queueJob->tentativas += 1;
        $queueJob->erro_mensagem = "Erro técnico: {$motivo}";

        if ($queueJob->tentativas >= $maxTentativas) {
            // Atingiu limite
            Log::error("❌ [QUEUE-EXPONENTIAL] QueueJob {$queueJob->id} FALHOU - Máx tentativas ({$maxTentativas}) atingido");
            $queueJob->status = 'failed';
            $queueJob->resultado = array_merge($queueJob->resultado ?? [], [
                'razao_falha' => 'erro_tecnico_max_tentativas',
                'motivo_erro' => $motivo
            ]);

        } else {
            // Exponential backoff: 2^tentativas * 60 segundos
            // Tentativa 1: 2 minutos
            // Tentativa 2: 4 minutos
            // Tentativa 3: 8 minutos
            $delaySegundos = pow(2, $queueJob->tentativas) * 60;
            $proximaTentativa = Carbon::now()->addSeconds($delaySegundos);

            Log::warning("🔄 [QUEUE-EXPONENTIAL] QueueJob {$queueJob->id} - Retry exponencial agendado");
            Log::warning("⏱️  Tentativa {$queueJob->tentativas}/{$maxTentativas} em " . round($delaySegundos / 60) . " minutos");

            $queueJob->status = 'pending';
            $queueJob->proxima_tentativa = $proximaTentativa;
            $queueJob->resultado = array_merge($queueJob->resultado ?? [], [
                'ultimo_erro_tecnico' => $motivo,
                'backoff_exponencial' => true,
                'delay_segundos' => $delaySegundos,
                'proximo_retry_em' => $proximaTentativa->toIso8601String()
            ]);
        }

        $queueJob->save();
        Log::info("💾 [QUEUE-SAVED] QueueJob {$queueJob->id} salvo com retry exponencial");

        if ($mailing) {
            $mailing->atualizarEstatisticas();
        }
    }

    /**
     * Validar assinatura do webhook (se Retell envia)
     */
    private function validateWebhookSignature(Request $request)
    {
        $signature = $request->header('X-Retell-Signature');

        // Tentar resolver empresa_id pelo metadata do request para buscar credenciais corretas
        $metadata = $request->input('metadata', []);
        $empresaId = $metadata['empresa_id'] ?? null;
        if (!$empresaId) {
            // Fallback: tentar via call_id → ligação → contato → empresa_id
            $callId = $request->input('call_id');
            if ($callId) {
                $ligacaoTemp = Ligacao::withoutGlobalScopes()->where('call_id_retell', $callId)->first();
                if ($ligacaoTemp) {
                    $empresaId = $ligacaoTemp->empresa_id;
                }
            }
        }
        $creds = IntegracaoService::getCredentials($empresaId);
        $secret = $creds['retell_webhook_secret'];

        // Se ambos existem, validar
        if ($signature && $secret) {
            Log::debug("🔐 [WEBHOOK-SIGNATURE] Validando assinatura");

            // Retell usa HMAC-SHA256 para assinar o payload
            $payload = $request->getContent();

            // Gera a assinatura esperada
            $expectedSignature = hash_hmac(
                'sha256',
                $payload,
                $secret,
                false
            );

            // Comparação com timing-safe (protege contra timing attacks)
            if (!hash_equals($expectedSignature, $signature)) {
                Log::error("❌ [WEBHOOK-SIGNATURE-INVALID] Assinatura inválida!");
                Log::error("  - Esperado: " . substr($expectedSignature, 0, 16) . "...");
                Log::error("  - Recebido: " . substr($signature, 0, 16) . "...");

                throw new \Exception('Webhook signature validation failed');
            }

            Log::info("✅ [WEBHOOK-SIGNATURE-VALID] Assinatura validada com sucesso");
        } else {
            // Se secret não está configurado, log de aviso
            if (!$secret) {
                Log::warning("⚠️  [WEBHOOK-SECRET-MISSING] RETELL_WEBHOOK_SECRET não configurado - webhook não será validado!");
            }
        }
    }

    /**
     * Test webhook - para validar setup
     * GET /api/ura/webhook/test
     */
    public function testWebhook()
    {
        Log::info("🧪 [WEBHOOK-TEST] Teste de webhook executado");

        return response()->json([
            'success' => true,
            'message' => 'Webhook test bem-sucedido',
            'timestamp' => Carbon::now()->toIso8601String(),
            'app_url' => config('app.url'),
        ]);
    }

    /**
     * Listar webhooks recebidos (para debug)
     * GET /api/ura/webhooks
     */
    public function listWebhooks()
    {
        try {
            // Buscar últimos 50 webhooks nos logs
            $logFile = storage_path('logs/laravel.log');

            if (!file_exists($logFile)) {
                return response()->json([
                    'webhooks' => [],
                    'total' => 0,
                    'message' => 'Nenhum log encontrado ainda',
                ]);
            }

            $logs = file_get_contents($logFile);
            $allLines = array_reverse(preg_split('/\n/', $logs));

            $webhookLines = array_filter(
                $allLines,
                function ($line) {
                    return !empty($line) && (strpos($line, '[WEBHOOK-') !== false || strpos($line, '[CALL-') !== false);
                }
            );

            $webhooks = array_slice($webhookLines, 0, 50);

            return response()->json([
                'webhooks' => array_values($webhooks),
                'total' => count($webhookLines),
                'showing' => count($webhooks),
            ]);

        } catch (\Exception $e) {
            Log::error("Erro ao listar webhooks: " . $e->getMessage());

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Simular webhook para testes
     * POST /api/ura/webhook/simulate
     */
    public function simulateWebhook(Request $request)
    {
        Log::info("🧪 [WEBHOOK-SIMULATE] Simulando webhook para teste");

        $data = [
            'call_id' => 'test_' . uniqid(),
            'call_status' => $request->input('status', 'completed'),
            'metadata' => [
                'contato_id' => $request->input('contato_id', 1),
            ],
            'duration' => $request->input('duration', 120),
        ];

        Log::debug("🧪 [WEBHOOK-SIMULATE-DATA] " . json_encode($data));

        // Chamar a mesma função que o webhook real
        return $this->handleRetellWebhook(new Request($data));
    }
}
