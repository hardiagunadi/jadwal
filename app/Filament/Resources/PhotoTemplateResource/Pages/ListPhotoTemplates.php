<?php

namespace App\Filament\Resources\PhotoTemplateResource\Pages;

use App\Filament\Resources\PhotoTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPhotoTemplates extends ListRecords
{
    protected static string $resource = PhotoTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
