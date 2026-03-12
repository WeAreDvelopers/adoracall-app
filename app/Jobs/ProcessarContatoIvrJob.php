<?php

namespace App\Jobs;

use App\Models\Contato;
use App\Models\QueueJob;
use App\Services\TwilioUraService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessarContatoIvrJob extends Job
{
    protected $queueJobId;

    public function __construct($queueJobId)
    {
        $this->queueJobId = $queueJobId;
    }

    public function handle()
    {
        $queueJob = QueueJob::withoutGlobalScopes()->find($this->queueJobId);

        if (!$queueJob) {
            Log::error("[IVR-JOB-NOTFOUND] QueueJob {$this->queueJobId} não encontrado");
            return;
        }

        // Bindar empresa_id no container para contexto CLI
        if ($queueJob->empresa_id) {
            app()->instance('empresa_id', $queueJob->empresa_id);
        }


        // Verifica se o mailing ainda está ativo
        $mailing = $queueJob->mailing;
        if ($mailing->isPausado() || $mailing->status === 'cancelado') {
            $queueJob->proxima_tentativa = Carbon::now()->addMinutes(5);
            $queueJob->save();
            return;
        }

        $contato = $queueJob->contato;


        try {
            $queueJob->marcarComoProcessando(gethostname());

            $start = microtime(true);

            $uraService = new TwilioUraService();
            $resultado  = $uraService->initiateCall(
                $contato,
                $queueJob->empresa_id,
                $mailing->id,
                $queueJob->id
            );

            $duration = microtime(true) - $start;

            // Marca job como aguardando callback do Twilio
            $queueJob->status    = 'awaiting_callback';
            $queueJob->resultado = [
                'call_sid'            => $resultado['call_sid'],
                'ura_call_id'         => $resultado['ura_call_id'],
                'ligacao_id'          => $resultado['ligacao_id'],
                'tipo'                => 'ivr_programatica',
                'iniciado_em'         => Carbon::now()->toIso8601String(),
                'tempo_requisicao_ms' => (int) ($duration * 1000),
            ];
            $queueJob->tempo_processamento = (int) ($duration * 1000);
            $queueJob->ultima_tentativa    = Carbon::now();
            $queueJob->save();

            // Atualizar estatísticas do mailing
            $mailing->atualizarEstatisticas();


        } catch (\Exception $e) {
            Log::error("[IVR-JOB-ERROR] Erro: {$e->getMessage()}");
            $this->handleFailure($queueJob, $e);
        }
    }

    protected function handleFailure(QueueJob $queueJob, \Exception $exception)
    {
        $contato = $queueJob->contato;
        $mailing = $queueJob->mailing;
        $script  = $mailing->script;

        $queueJob->tentativas    += 1;
        $queueJob->erro_mensagem = substr($exception->getMessage(), 0, 500);

        $maxTentativas = $mailing->max_tentativas ?? $script->tentativas_max ?? 3;

        if ($queueJob->tentativas >= $maxTentativas) {
            $queueJob->status   = 'failed';
            $contato->status    = 'falhou';
            $contato->resultado = 'max_tentativas_atingido';
            $contato->save();
            Log::error("[IVR-JOB-PERMANENT-FAIL] Job {$this->queueJobId} falhou após {$queueJob->tentativas} tentativas");
        } else {
            $intervalo = $mailing->intervalo_retry ?? $script->intervalo_entre_tentativas ?? 3600;
            $delay     = $intervalo * pow(2, $queueJob->tentativas - 1);

            $queueJob->proxima_tentativa = Carbon::now()->addSeconds($delay);
            $queueJob->status            = 'pending';

        }

        $queueJob->save();
        $mailing->atualizarEstatisticas();
    }

    public function failed(\Exception $exception)
    {
        Log::critical("[IVR-JOB-CRITICAL] Job {$this->queueJobId} falhou criticamente: {$exception->getMessage()}");

        $queueJob = QueueJob::withoutGlobalScopes()->find($this->queueJobId);
        if ($queueJob) {
            $queueJob->status        = 'failed';
            $queueJob->erro_mensagem = 'Falha crítica IVR: ' . $exception->getMessage();
            $queueJob->save();
        }
    }
}
