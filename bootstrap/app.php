<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use App\Console\Commands\KirimPengingatTindakLanjut;

return Application::configure(basePath: dirname(__DIR__))
    ->withCommands([
        KirimPengingatTindakLanjut::class,
    ])
    ->withSchedule(function (Schedule $schedule) {
        // Jalankan scheduler pengingat TL setiap menit agar window 5 jam dan pengingat akhir tidak terlewat.
        $schedule->command('surat:ingatkan-tl')->everyMinute();
        $schedule->command('vehicle-taxes:send-reminders')->dailyAt('08:00');
        $schedule->command('reminders:send-follow-up')->everyFiveMinutes();
        $schedule->command('kegiatan:escalate-disposisi')->everyFiveMinutes();
        $schedule->command('kegiatan:remind-disposisi-h-1 --stage=arsiparis')->dailyAt('13:00');
        $schedule->command('kegiatan:remind-disposisi-h-1 --stage=group')->dailyAt('17:00');
    })
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        channels: __DIR__.'/../routes/channels.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
