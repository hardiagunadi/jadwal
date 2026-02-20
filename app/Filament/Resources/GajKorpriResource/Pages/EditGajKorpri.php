<?php

namespace App\Filament\Resources\GajKorpriResource\Pages;

use App\Filament\Resources\GajKorpriResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGajKorpri extends EditRecord
{
    protected static string $resource = GajKorpriResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
