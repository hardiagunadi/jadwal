<?php

namespace App\Filament\Resources\SpjResource\Pages;

use App\Filament\Resources\SpjResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSpj extends EditRecord
{
    protected static string $resource = SpjResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->formId('form'),

            $this->getCancelFormAction()
                ->url(SpjResource::getUrl('index')),

            Action::make('cetak_kwitansi')
                ->label('Cetak Kwitansi')
                ->icon('heroicon-o-printer')
                ->url(fn () => route('spj.kwitansi.cetak', $this->getRecord()))
                ->openUrlInNewTab()
                ->color('success'),

            DeleteAction::make()
                ->hidden(fn ($record) => blank($record) ? false : (method_exists($record, 'trashed') && $record->trashed())),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
