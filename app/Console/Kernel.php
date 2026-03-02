<?php

namespace App\Console;

use App\Console\Commands\KirimPengingatTindakLanjut;
use App\Console\Commands\SendFollowUpReminders;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array<int, class-string>
     */
    protected $commands = [
        \App\Console\Commands\KirimPengingatTindakLanjut::class,
        \App\Console\Commands\SendVehicleTaxReminders::class,
        SendFollowUpReminders::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Jalankan lebih sering agar pengiriman H-1 menit tidak terlewat.
        $schedule->command('surat:ingatkan-tl')->everyMinute();
        $schedule->command('vehicle-taxes:send-reminders')->dailyAt('08:00');
        $schedule->command('reminders:send-follow-up')->everyFiveMinutes();
        $schedule->command('kegiatan:escalate-disposisi')->everyFiveMinutes();
        $schedule->command('kegiatan:remind-disposisi-h-1')->dailyAt('17:00');

        // Clear laravel.log setiap hari jam 00:00
        $schedule->call(function () {
            $path = storage_path('logs/laravel.log');
            if (file_exists($path)) {
                file_put_contents($path, '');
            }
        })->dailyAt('00:00')->name('clear-laravel-log')->withoutOverlapping();

        // Clear scheduler.log setiap 7 hari (Senin) jam 00:00
        $schedule->call(function () {
            $path = storage_path('logs/scheduler.log');
            if (file_exists($path)) {
                file_put_contents($path, '');
            }
        })->weeklyOn(1, '00:00')->name('clear-scheduler-log')->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
