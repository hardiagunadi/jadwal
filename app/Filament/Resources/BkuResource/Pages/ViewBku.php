<?php

namespace App\Filament\Resources\BkuResource\Pages;

use App\Filament\Resources\BkuResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBku extends ViewRecord
{
    protected static string $resource = BkuResource::class;

    public function getTitle(): string
    {
        return 'BKU: ' . $this->record->nama_label;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cetak_bku')
                ->label('Cetak BKU')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->url(fn () => route('bku.cetak', $this->record))
                ->openUrlInNewTab(),

            Action::make('cetak_pemindahbukuan')
                ->label('Cetak Pemindahbukuan')
                ->icon('heroicon-o-building-library')
                ->color('info')
                ->url(fn () => route('bku.pemindahbukuan', $this->record))
                ->openUrlInNewTab(),

            EditAction::make(),
        ];
    }
}
