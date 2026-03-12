<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EmpresaConfiguracao;
use App\Models\QueueJob;
use App\Models\Mailing;
use App\Jobs\ProcessarContatoJob;
use App\Jobs\ProcessarContatoIvrJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ProcessarFilaLigacoes extends Command
{
    protected $signature = 'ligacoes:processar {--loop : Rodar em loop contínuo}';
    protected $description = 'Processa fila de ligações respeitando velocidade configurada';

    public function handle()
    {
        $loop = $this->option('loop');

        do {
            $timestamp = Carbon::now()->format('H:i:s');
            $this->info('[' . $timestamp . '] 🔍 Verificando jobs pendentes...');

            // Buscar mailings ativos (sem Global Scope - worker processa todas as empresas)
            $mailingsAtivos = Mailing::withoutGlobalScopes()->where('status', 'ativo')->get();

            if ($mailingsAtivos->isEmpty()) {
                $this->info('⏸️ Nenhuma campanha ativa no momento');
            } else {
                foreach ($mailingsAtivos as $mailing) {
                    // Limpar contexto de empresa anterior para evitar sangramento entre iterações
                    app()->forgetInstance('empresa_id');
                    $this->processarMailing($mailing);
                }
            }

            if ($loop) {
                $this->info('⏱️ Aguardando 10 segundos...');
                sleep(10);
            }

        } while ($loop);

        $this->info('✅ Processamento concluído');
    }

    protected function processarMailing(Mailing $mailing)
    {
        // Calcular quantos jobs podem ser processados neste ciclo
        $velocidade = $mailing->velocidade_contatos_hora; // ex: 60 contatos/hora
        $jobsPorCiclo = ceil($velocidade / 360); // 360 ciclos de 10s por hora


        // Setar contexto da empresa para este mailing
        app()->instance('empresa_id', $mailing->empresa_id);

        // Buscar jobs pendentes (sem Global Scope - worker em CLI)
        $jobs = QueueJob::withoutGlobalScopes()
            ->where('mailing_id', $mailing->id)
            ->pendentes()
            ->limit($jobsPorCiclo)
            ->get();

        if ($jobs->isEmpty()) {
            return;
        }

        $this->info("🔄 Mailing {$mailing->id} ({$mailing->nome}): Despachando {$jobs->count()} jobs");

        foreach ($jobs as $job) {
            // Processar job diretamente (sem usar queue system do Laravel)
            try {

                // Marcar como processing
                $job->status = 'processing';
                $job->save();

                // Determinar modo de ligação por empresa (fallback: env global)
                $config = EmpresaConfiguracao::where('empresa_id', $job->empresa_id)->first();
                $modo = $config->modo_ligacao ?? (env('USE_IVR_MODE', true) ? 'ivr' : 'retell');

                $jobProcessor = ($modo === 'retell')
                    ? new ProcessarContatoJob($job->id)
                    : new ProcessarContatoIvrJob($job->id);
                $jobProcessor->handle();


            } catch (\Exception $e) {
                Log::error("❌ [WORKER-JOB-ERROR] Erro ao processar job {$job->id}: " . $e->getMessage());
                Log::error("Stack trace: " . $e->getTraceAsString());
                $job->status = 'failed';
                $job->save();
            }
        }

    }
}
