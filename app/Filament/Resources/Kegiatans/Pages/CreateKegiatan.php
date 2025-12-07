<?php

namespace App\Filament\Resources\Kegiatans\Pages;

use App\Filament\Resources\Kegiatans\KegiatanResource;
use App\Filament\Resources\Kegiatans\Concerns\HandlesCaptionGeneration;
use Filament\Resources\Pages\CreateRecord;

class CreateKegiatan extends CreateRecord
{
    use HandlesCaptionGeneration;

    protected static string $resource = KegiatanResource::class;
}
