<?php

namespace App\Filament\Resources\GajKorpriResource\Pages;

use App\Filament\Resources\GajKorpriResource;
use App\Models\GajKorpri;
use App\Models\GajPegawai;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateGajKorpri extends CreateRecord
{
    protected static string $resource = GajKorpriResource::class;

    /**
     * Buat satu record per NIP yang dipilih (multi-select).
     * Jika NIP sudah ada, jumlahnya diperbarui (updateOrCreate).
     */
    protected function handleRecordCreation(array $data): Model
    {
        $nips   = (array) ($data['nips'] ?? []);
        $jumlah = (int) ($data['jumlah'] ?? 20000);

        $last = null;
        foreach ($nips as $nip) {
            $nama = GajPegawai::where('nip', $nip)->value('nama') ?? '';
            $last = GajKorpri::updateOrCreate(
                ['nip' => $nip],
                ['nama' => $nama, 'jumlah' => $jumlah],
            );
        }

        // Filament butuh Model dikembalikan; fallback jika list kosong
        return $last ?? new GajKorpri();
    }
}
