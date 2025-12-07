<?php

namespace App\Filament\Resources\Kegiatans\Pages;

use App\Filament\Resources\Kegiatans\KegiatanResource;
use App\Filament\Resources\Kegiatans\Concerns\HandlesCaptionGeneration;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKegiatan extends EditRecord
{
    use HandlesCaptionGeneration;

    protected static string $resource = KegiatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
