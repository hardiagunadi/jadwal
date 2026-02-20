<?php

namespace App\Filament\Resources\KodeSuratResource\Pages;

use App\Filament\Resources\KodeSuratResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKodeSurats extends ListRecords
{
    protected static string $resource = KodeSuratResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn () => auth()->user()?->isAdmin()),
        ];
    }
}
