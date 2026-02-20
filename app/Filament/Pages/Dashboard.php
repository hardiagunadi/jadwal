<?php

namespace App\Filament\Pages;

use App\Models\SeksiModul;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getTitle(): string
    {
        $user = auth()->user();

        if (! $user || $user->isAdmin()) {
            return 'Dasbor';
        }

        $akronim = strtolower(trim((string) ($user->jabatan_akronim ?? '')));

        if ($akronim === '') {
            return 'Dasbor';
        }

        $label = SeksiModul::labelSeksi($akronim) ?? strtoupper($akronim);

        return 'Dasbor ' . $label;
    }

    public static function getNavigationLabel(): string
    {
        $user = auth()->user();

        if (! $user || $user->isAdmin()) {
            return 'Dasbor';
        }

        $akronim = strtolower(trim((string) ($user->jabatan_akronim ?? '')));

        if ($akronim === '') {
            return 'Dasbor';
        }

        $label = SeksiModul::labelSeksi($akronim) ?? strtoupper($akronim);

        return 'Dasbor ' . $label;
    }
}
