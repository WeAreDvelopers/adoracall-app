<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\SyncRetellCalls::class,
        Commands\ProcessarFilaLigacoes::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Sincronizar chamadas da Retell AI a cada 5 minutos
        $schedule->command('retell:sync-calls --days=1')
                 ->everyFiveMinutes()
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/retell-sync.log'));

        // Resincronizar chamadas pendentes a cada hora
        $schedule->command('retell:sync-calls --resync')
                 ->hourly()
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/retell-resync.log'));

        // Limpeza de registros antigos - uma vez por semana aos domingos às 3h
        $schedule->command('retell:sync-calls --cleanup')
                 ->weekly()
                 ->sundays()
                 ->at('03:00')
                 ->appendOutputTo(storage_path('logs/retell-cleanup.log'));
    }
}
