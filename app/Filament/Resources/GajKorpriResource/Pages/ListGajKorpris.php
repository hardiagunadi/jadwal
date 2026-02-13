<?php

namespace App\Filament\Resources\GajKorpriResource\Pages;

use App\Filament\Resources\GajKorpriResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGajKorpris extends ListRecords
{
    protected static string $resource = GajKorpriResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
