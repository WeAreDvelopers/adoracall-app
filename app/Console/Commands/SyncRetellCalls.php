<?php

namespace App\Console\Commands;

use App\Services\RetellSyncService;
use Illuminate\Console\Command;

class SyncRetellCalls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'retell:sync-calls
                            {--days=7 : Número de dias para sincronizar}
                            {--all : Sincronizar todos os registros disponíveis com paginação}
                            {--resync : Resincronizar chamadas pendentes}
                            {--cleanup : Limpar registros antigos (90+ dias)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza chamadas da Retell AI para o banco de dados local';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(RetellSyncService $syncService)
    {
        $this->info('🚀 Iniciando sincronização de chamadas Retell AI...');
        $this->newLine();

        try {
            // Resincronizar pendentes
            if ($this->option('resync')) {
                $this->info('🔄 Resincronizando chamadas pendentes...');
                $stats = $syncService->resincronizarPendentes();
                $this->displayResyncStats($stats);
                $this->newLine();
            }

            // Limpeza de registros antigos
            if ($this->option('cleanup')) {
                $this->info('🗑️ Limpando registros antigos...');
                $deletados = $syncService->limparRegistrosAntigos();
                $this->info("✅ {$deletados} registros antigos removidos");
                $this->newLine();
            }

            // Sincronização principal
            if (!$this->option('resync') && !$this->option('cleanup')) {
                $dias = (int) $this->option('days');
                $syncAll = $this->option('all');

                if ($syncAll) {
                    $this->info('📥 Sincronizando TODAS as chamadas disponíveis...');
                    $this->warn('⚠️ Esta operação pode demorar bastante!');
                    $this->newLine();

                    $totalStats = [
                        'total' => 0,
                        'created' => 0,
                        'updated' => 0,
                        'errors' => 0,
                    ];

                    $paginationKey = null;
                    $page = 1;

                    do {
                        $this->info("📄 Processando página {$page}...");

                        $stats = $syncService->sincronizarChamadas($dias, $paginationKey);

                        // Acumular estatísticas
                        $totalStats['total'] += $stats['total'];
                        $totalStats['created'] += $stats['created'];
                        $totalStats['updated'] += $stats['updated'];
                        $totalStats['errors'] += $stats['errors'];

                        $this->displaySyncStats($stats);

                        $paginationKey = $stats['next_pagination_key'];
                        $page++;

                        if ($paginationKey) {
                            $this->info('➡️ Próxima página disponível...');
                            $this->newLine();
                        }

                    } while ($paginationKey);

                    $this->newLine();
                    $this->info('✅ Sincronização completa finalizada!');
                    $this->displaySyncStats($totalStats);

                } else {
                    $this->info("📥 Sincronizando chamadas dos últimos {$dias} dias...");
                    $stats = $syncService->sincronizarChamadas($dias);
                    $this->displaySyncStats($stats);

                    if ($stats['next_pagination_key']) {
                        $this->newLine();
                        $this->warn('⚠️ Mais registros disponíveis. Use --all para sincronizar tudo.');
                    }
                }
            }

            $this->newLine();
            $this->info('🎉 Processo concluído com sucesso!');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Erro durante sincronização: ' . $e->getMessage());
            $this->error($e->getTraceAsString());

            return Command::FAILURE;
        }
    }

    /**
     * Exibe estatísticas de sincronização
     */
    private function displaySyncStats(array $stats): void
    {
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Total de Chamadas', $stats['total']],
                ['Criadas', $stats['created']],
                ['Atualizadas', $stats['updated']],
                ['Erros', $stats['errors']],
            ]
        );
    }

    /**
     * Exibe estatísticas de resincronização
     */
    private function displayResyncStats(array $stats): void
    {
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Total Pendentes', $stats['total']],
                ['Sucesso', $stats['success']],
                ['Erros', $stats['errors']],
            ]
        );
    }
}
