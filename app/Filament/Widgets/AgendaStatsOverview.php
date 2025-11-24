<?php

namespace App\Filament\Widgets;

use App\Models\Kegiatan;
use App\Models\Personil;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class AgendaStatsOverview extends StatsOverviewWidget
{
    // Optional: auto refresh tiap 30 detik
    protected static ?string $pollingInterval = '30s';

    protected function getCards(): array
    {
        $today = Carbon::today();
        $startOfWeek = $today->copy()->startOfWeek(Carbon::MONDAY);
        $endOfWeek   = $today->copy()->endOfWeek(Carbon::SUNDAY);
        $in7Days     = $today->copy()->addDays(7);

        $totalAgenda          = Kegiatan::count();
        $agendaHariIni        = Kegiatan::whereDate('tanggal', $today)->count();
        $agendaMingguIni      = Kegiatan::whereBetween('tanggal', [$startOfWeek, $endOfWeek])->count();
        $agenda7HariKeDepan   = Kegiatan::whereBetween('tanggal', [$today, $in7Days])->count();
        $agendaBelumDisposisi = Kegiatan::where('sudah_disposisi', false)->count();
        $totalPersonil        = Personil::count();

        return [
            Card::make('Agenda Hari Ini', $agendaHariIni)
                ->description('Kegiatan pada hari ini')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color($agendaHariIni > 0 ? 'success' : 'gray'),

            Card::make('Belum Disposisi', $agendaBelumDisposisi)
                ->description('Menunggu disposisi pimpinan')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($agendaBelumDisposisi > 0 ? 'warning' : 'success'),

            Card::make('Agenda 7 Hari ke Depan', $agenda7HariKeDepan)
                ->description('Termasuk hari ini')
                ->descriptionIcon('heroicon-o-forward')
                ->color($agenda7HariKeDepan > 0 ? 'info' : 'gray'),

            Card::make('Total Agenda', $totalAgenda)
                ->description('Semua agenda terdata')
                ->descriptionIcon('heroicon-o-archive-box')
                ->color('gray'),

            Card::make('Total Personil', $totalPersonil)
                ->description('Personil yang terdaftar')
                ->descriptionIcon('heroicon-o-user-group')
                ->color('gray'),
        ];
    }
}
