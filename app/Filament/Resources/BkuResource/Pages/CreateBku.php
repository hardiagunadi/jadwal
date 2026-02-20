<?php

namespace App\Filament\Resources\BkuResource\Pages;

use App\Filament\Resources\BkuResource;
use App\Models\Bku;
use Filament\Resources\Pages\CreateRecord;

class CreateBku extends CreateRecord
{
    protected static string $resource = BkuResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $maxUrut = Bku::where('tahun', $data['tahun'] ?? now()->year)->max('nomor_urut') ?? 0;
        $data['nomor_urut'] = $maxUrut + 1;
        $data['tanggal_generate'] = now()->toDateString();

        return $data;
    }
}
