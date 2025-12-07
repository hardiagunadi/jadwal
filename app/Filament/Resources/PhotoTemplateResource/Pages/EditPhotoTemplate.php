<?php

namespace App\Filament\Resources\PhotoTemplateResource\Pages;

use App\Filament\Resources\PhotoTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPhotoTemplate extends EditRecord
{
    protected static string $resource = PhotoTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
