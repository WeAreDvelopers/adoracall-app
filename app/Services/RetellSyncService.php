<?php

namespace App\Services;

use App\Models\CallHistory;
use App\Models\Ligacao;
use App\Models\Contato;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RetellSyncService
{
    private $client;
    private $apiKey;
    private $empresaId;

    public function __construct(?int $empresaId = null)
    {
        $this->client = new Client();
        $this->empresaId = $empresaId;
        $creds = IntegracaoService::getCredentials($empresaId);
        $this->apiKey = $creds['retell_api_key'];
    }

    /**
     * Sincroniza chamadas dos últimos N dias
     */
    public function sincronizarChamadas(int $dias = 30, ?string $paginationKey = null): array
    {
        try {

            $requestBody = [
                'filter_criteria' => [
                    'start_timestamp' => [
                        'lower_threshold' => strtotime("-{$dias} days") * 1000,
                        'upper_threshold' => time() * 1000,
                    ],
                ],
                'sort_order' => 'descending',
                'limit' => 100, // Máximo para sync em batch
            ];

            // Filtrar por agent_id se configurado
            // if (env('RETELL_AGENT_ID')) {
            //     $requestBody['filter_criteria']['agent_id'] = [env('RETELL_AGENT_ID')];
            // }

            if ($paginationKey) {
                $requestBody['pagination_key'] = $paginationKey;
            }

            $response = $this->client->post('https://api.retellai.com/v2/list-calls', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $requestBody,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $calls = is_array($data) && isset($data[0]) ? $data : [];


            $stats = [
                'total' => count($calls),
                'created' => 0,
                'updated' => 0,
                'errors' => 0,
                'next_pagination_key' => $data['pagination_key'] ?? null,
            ];

            foreach ($calls as $callData) {
                try {
                    $result = $this->processarChamada($callData);
                    if ($result['created']) {
                        $stats['created']++;
                    } else {
                        $stats['updated']++;
                    }
                } catch (\Exception $e) {
                    $stats['errors']++;
                    Log::error("❌ Erro ao processar chamada {$callData['call_id']}: " . $e->getMessage());
                }
            }


            return $stats;

        } catch (\Exception $e) {
            Log::error("❌ Erro na sincronização: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Processa uma chamada individual
     */
    private function processarChamada(array $callData): array
    {
        $chamada = json_encode($callData, true);
        $callId = $callData['call_id'];

        // Buscar ou criar registro (sem Global Scope - sync processa todas as empresas)
        $callHistory = CallHistory::withoutGlobalScopes()->firstOrNew(['call_id_retell' => $callId]);
        $wasNew = !$callHistory->exists;

        // Buscar ligação local relacionada (sem Global Scope)
        $ligacao = Ligacao::withoutGlobalScopes()->where('call_id_retell', $callId)->with('contato')->first();

        // Dados básicos da chamada
        $callHistory->agent_id = $callData['agent_id'] ?? null;
        $callHistory->to_number = $callData['to_number'] ?? null;
        $callHistory->from_number = $callData['from_number'] ?? null;
        $callHistory->call_status = $callData['call_status'] ?? 'unknown';
        $callHistory->call_type = $callData['call_type'] ?? null;
        $callHistory->direction = $callData['direction'] ?? null;

        // Timestamps
        if (isset($callData['start_timestamp'])) {
            $callHistory->start_timestamp = Carbon::createFromTimestampMs($callData['start_timestamp']);
        }
        if (isset($callData['end_timestamp'])) {
            $callHistory->end_timestamp = Carbon::createFromTimestampMs($callData['end_timestamp']);
        }

        // Duração e análise
        $durationSeconds = $callData['call_cost']['total_duration_seconds'] ?? 0;
        $callHistory->duration_ms = $durationSeconds;
        $callHistory->user_sentiment = $callData['call_analysis']['user_sentiment'] ?? 'Desconhecido';
        $callHistory->call_successful = $callData['call_analysis']['call_successful'] ?? false;

        // Resumo e transcrição
        $callHistory->call_summary = $callData['call_analysis']['call_summary']['summary'] ?? null;
        $callHistory->transcript = $callData['transcript'] ?? null;
        $callHistory->transcript_preview = $this->gerarPreview($callData['transcript'] ?? '', 200);

        // Gravação e custo
        $callHistory->recording_url = $callData['recording_url'] ?? null;
        $callHistory->cost = $callData['call_cost']['combined_cost'] ?? 0;

        // Formatar dados para exibição
        $callHistory->cost_formatted = $callHistory->cost;
        // $callHistory->cost_formatted = $this->formatarCusto($callData['cost'] ?? 0);
        $callHistory->duration_formatted = $callHistory->duration_ms;
        // $callHistory->duration_formatted = $this->formatarDuracao($callHistory->duration_ms);

        // Metadados
        $callHistory->metadata = $callData['metadata'] ?? null;
        $callHistory->raw_data = $callData; // Salvar dados brutos completos

        // Setar empresa_id a partir dos metadados (quando não há ligação local)
        if (empty($callHistory->empresa_id) && !empty($callData['metadata']['empresa_id'])) {
            $callHistory->empresa_id = $callData['metadata']['empresa_id'];
        }

        // Enriquecer com dados locais se houver ligação
        if ($ligacao) {
            $callHistory->ligacao_id = $ligacao->id;
            $callHistory->empresa_id = $ligacao->empresa_id;

            if ($ligacao->contato) {
                $callHistory->contato_id = $ligacao->contato->id;
                $callHistory->cliente_nome = $ligacao->contato->nome_completo;
                $callHistory->empresa_credora = $ligacao->contato->empresa_credora;
                $callHistory->valor_devido = $ligacao->contato->valor_debito;
                $callHistory->vencimento = $ligacao->contato->vencimento;
                $callHistory->status_local = $ligacao->status;
                $callHistory->tentativas = $ligacao->contato->tentativas;
            }

            // ========== SINCRONIZAR TABELA LIGACAO ==========
            $this->sincronizarLigacao($ligacao, $callData, $callHistory);
        }

        // Marcar como sincronizado
        $callHistory->synced_at = Carbon::now();
        $callHistory->needs_resync = false;

        $callHistory->save();

        return [
            'created' => $wasNew,
            'call_history' => $callHistory,
        ];
    }

    /**
     * Sincroniza os dados da Retell para a tabela Ligacao local
     */
    private function sincronizarLigacao(Ligacao $ligacao, array $callData, CallHistory $callHistory): void
    {
        try {
            // ===== STATUS DA LIGAÇÃO =====
            // Mapear call_status da Retell para status local
            $callStatus = $callData['call_status'] ?? null;
            $statusMap = [
                'initiated' => 'iniciada',
                'ongoing' => 'em_andamento',
                'completed' => 'concluida',
                'failed' => 'falha',
                'timeout' => 'timeout',
            ];
            $ligacao->status = $statusMap[$callStatus] ?? $callStatus ?? 'desconhecido';

            // ===== DURAÇÃO =====
            // Converter segundos para inteiro
            $ligacao->duracao = (int)($callData['call_cost']['total_duration_seconds'] ?? 0);

            // ===== FOI ATENDIDA =====
            // Considerar como atendida se teve duração > 0 ou se a análise diz que foi bem-sucedida
            $durationSeconds = $callData['call_cost']['total_duration_seconds'] ?? 0;
            $callSuccessful = $callData['call_analysis']['call_successful'] ?? false;
            $ligacao->foi_atendida = ($durationSeconds > 0 || $callSuccessful);

            // ===== URL DE GRAVAÇÃO =====
            if (isset($callData['recording_url'])) {
                $ligacao->url_gravacao = $callData['recording_url'];
            }

            // ===== RESULTADO =====
            // Armazenar sentimento do usuário ou resultado da análise
            $userSentiment = $callData['call_analysis']['user_sentiment'] ?? null;
            if ($userSentiment) {
                $ligacao->resultado = strtolower($userSentiment);
            }

            // ===== DETALHES (JSON) =====
            // Armazenar dados completos da análise em detalhes
            $detalhes = [
                'user_sentiment' => $callData['call_analysis']['user_sentiment'] ?? null,
                'call_successful' => $callData['call_analysis']['call_successful'] ?? null,
                'summary' => $callData['call_analysis']['call_summary']['summary'] ?? null,
                'to_number' => $callData['to_number'] ?? null,
                'from_number' => $callData['from_number'] ?? null,
                'agent_id' => $callData['agent_id'] ?? null,
                'call_type' => $callData['call_type'] ?? null,
                'direction' => $callData['direction'] ?? null,
                'call_cost' => $callData['call_cost'] ?? null,
            ];
            $ligacao->detalhes = $detalhes;

            // ===== SINCRONIZAÇÃO =====
            $ligacao->sincronizado_retell = true;
            $ligacao->ultimo_sync_retell = Carbon::now();

            // Salvar a ligação atualizada
            $ligacao->save();


        } catch (\Exception $e) {
            Log::error("❌ Erro ao sincronizar Ligacao ID {$ligacao->id}: " . $e->getMessage(), [
                'call_id' => $callData['call_id'] ?? null,
                'stack' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Busca detalhes de uma chamada específica e sincroniza
     */
    public function sincronizarChamadaEspecifica(string $callId): CallHistory
    {
        try {

            $response = $this->client->get("https://api.retellai.com/v2/get-call/{$callId}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
            ]);

            $callData = json_decode($response->getBody()->getContents(), true);

            $result = $this->processarChamada($callData);


            return $result['call_history'];

        } catch (\Exception $e) {
            Log::error("❌ Erro ao sincronizar chamada {$callId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Resincroniza chamadas que precisam de atualização
     */
    public function resincronizarPendentes(): array
    {
        $pendentes = CallHistory::precisaResync()->get();


        $stats = [
            'total' => $pendentes->count(),
            'success' => 0,
            'errors' => 0,
        ];

        foreach ($pendentes as $call) {
            try {
                $this->sincronizarChamadaEspecifica($call->call_id_retell);
                $stats['success']++;
            } catch (\Exception $e) {
                $stats['errors']++;
                Log::error("❌ Erro ao resincronizar {$call->call_id_retell}: " . $e->getMessage());
            }
        }


        return $stats;
    }

    /**
     * Limpa registros antigos (opcional - manutenção)
     */
    public function limparRegistrosAntigos(int $diasParaManter = 90): int
    {
        $dataLimite = Carbon::now()->subDays($diasParaManter);

        $deletados = CallHistory::where('start_timestamp', '<', $dataLimite)
            ->where('needs_resync', false)
            ->delete();


        return $deletados;
    }

    // ============================================
    // MÉTODOS AUXILIARES
    // ============================================

    private function gerarPreview(string $text, int $maxLength = 200): string
    {
        if (strlen($text) <= $maxLength) {
            return $text;
        }

        return substr($text, 0, $maxLength) . '...';
    }

    private function formatarDuracao(int $durationMs): string
    {
        $seconds = floor($durationMs / 1000);
        $minutes = floor($seconds / 60);
        $seconds = $seconds % 60;

        if ($minutes > 0) {
            return sprintf('%dm %ds', $minutes, $seconds);
        }

        return sprintf('%ds', $seconds);
    }

    private function formatarCusto(float $cost): string
    {
        return '$' . number_format($cost, 4);
    }
}
