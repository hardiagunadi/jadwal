<?php

namespace App\Filament\Resources\SpjResource\Pages;

use App\Filament\Resources\SpjResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSpjs extends ListRecords
{
    protected static string $resource = SpjResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
