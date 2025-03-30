<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * These schedules are run in a single process, so avoid doing any heavy processing here.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire:daily')->hourly();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        // Register the command
        $this->commands([
            \App\Console\Commands\CreateItoTemplateCommand::class,
            \App\Console\Commands\FixImportStatusCommand::class,
            \App\Console\Commands\FixItoImportCommand::class,
            \App\Console\Commands\DiagnoseImportCommand::class,
            \App\Console\Commands\FixImportIssuesCommand::class,
            \App\Console\Commands\InspectCsvCommand::class,
            \App\Console\Commands\EmptyTableCommand::class,
        ]);

        require base_path('routes/console.php');
    }
} 