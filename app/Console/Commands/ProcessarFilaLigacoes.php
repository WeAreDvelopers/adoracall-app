<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\QueueJob;
use App\Models\Mailing;
use App\Jobs\ProcessarContatoJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ProcessarFilaLigacoes extends Command
{
    protected $signature = 'ligacoes:processar {--loop : Rodar em loop contínuo}';
    protected $description = 'Processa fila de ligações respeitando velocidade configurada';

    public function handle()
    {
        $loop = $this->option('loop');
        Log::info("🚀 [WORKER-START] Iniciando ProcessarFilaLigacoes (loop: " . ($loop ? 'SIM' : 'NAO') . ")");

        do {
            $timestamp = Carbon::now()->format('H:i:s');
            $this->info('[' . $timestamp . '] 🔍 Verificando jobs pendentes...');
            Log::info("🔍 [WORKER-CYCLE] Ciclo iniciado às {$timestamp}");

            // Buscar mailings ativos (sem Global Scope - worker processa todas as empresas)
            $mailingsAtivos = Mailing::withoutGlobalScopes()->where('status', 'ativo')->get();
            Log::info("📊 [WORKER-MAILINGS] Encontradas " . $mailingsAtivos->count() . " campanhas ativas");

            if ($mailingsAtivos->isEmpty()) {
                $this->info('⏸️ Nenhuma campanha ativa no momento');
                Log::info("⏸️  [WORKER-IDLE] Nenhuma campanha ativa");
            } else {
                foreach ($mailingsAtivos as $mailing) {
                    // Limpar contexto de empresa anterior para evitar sangramento entre iterações
                    app()->forgetInstance('empresa_id');
                    $this->processarMailing($mailing);
                }
            }

            if ($loop) {
                $this->info('⏱️ Aguardando 10 segundos...');
                Log::debug("⏱️  [WORKER-SLEEP] Aguardando próximo ciclo (10s)");
                sleep(10);
            }

        } while ($loop);

        Log::info("✅ [WORKER-END] Processamento concluído");
        $this->info('✅ Processamento concluído');
    }

    protected function processarMailing(Mailing $mailing)
    {
        // Calcular quantos jobs podem ser processados neste ciclo
        $velocidade = $mailing->velocidade_contatos_hora; // ex: 60 contatos/hora
        $jobsPorCiclo = ceil($velocidade / 360); // 360 ciclos de 10s por hora

        Log::info("⚙️  [WORKER-CALC] Mailing {$mailing->id} ({$mailing->nome}): Velocidade={$velocidade}/h | JobsPorCiclo={$jobsPorCiclo}");

        // Setar contexto da empresa para este mailing
        app()->instance('empresa_id', $mailing->empresa_id);

        // Buscar jobs pendentes (sem Global Scope - worker em CLI)
        $jobs = QueueJob::withoutGlobalScopes()
            ->where('mailing_id', $mailing->id)
            ->pendentes()
            ->limit($jobsPorCiclo)
            ->get();

        if ($jobs->isEmpty()) {
            Log::debug("⏸️  [WORKER-NO-JOBS] Mailing {$mailing->id}: Nenhum job pendente");
            return;
        }

        Log::info("🔄 [WORKER-DISPATCH] Mailing {$mailing->id} ({$mailing->nome}): Despachando {$jobs->count()} jobs");
        $this->info("🔄 Mailing {$mailing->id} ({$mailing->nome}): Despachando {$jobs->count()} jobs");

        foreach ($jobs as $job) {
            // Processar job diretamente (sem usar queue system do Laravel)
            try {
                Log::info("🔄 [JOB-PROCESSING-DIRECT] Iniciando processamento direto do job {$job->id}");

                // Marcar como processing
                $job->status = 'processing';
                $job->save();

                // Executar o handle do job
                $jobProcessor = new ProcessarContatoJob($job->id);
                $jobProcessor->handle();

                Log::debug("✅ [WORKER-JOB-PROCESSED] Job {$job->id} processado com sucesso (Contato: {$job->contato->nome})");
            } catch (\Exception $e) {
                Log::error("❌ [WORKER-JOB-ERROR] Erro ao processar job {$job->id}: " . $e->getMessage());
                Log::error("Stack trace: " . $e->getTraceAsString());
                $job->status = 'failed';
                $job->save();
            }
        }

        Log::info("✅ [WORKER-DISPATCH-COMPLETE] {$jobs->count()} jobs processados com sucesso");
    }
}
