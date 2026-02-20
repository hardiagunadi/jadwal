<?php

namespace App\Filament\Resources\GajResource\Pages;

use App\Filament\Resources\GajResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewGaj extends ViewRecord
{
    protected static string $resource = GajResource::class;

    public function getTitle(): string
    {
        /** @var \App\Models\Gaj $record */
        $record = $this->record;

        return strtoupper($record->jenis) . ' — ' . $record->periode;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('perbedaan')
                ->label('Perbedaan')
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->color('info')
                ->url(fn () => route('gaj.export.perbedaan', $this->record))
                ->openUrlInNewTab(),

            Action::make('rincian')
                ->label('Rincian')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->url(fn () => route('gaj.export.rincian', $this->record))
                ->openUrlInNewTab(),

            Action::make('jml_peg')
                ->label('Jml. Peg')
                ->icon('heroicon-o-user-group')
                ->color('warning')
                ->url(fn () => route('gaj.export.jml-peg', $this->record))
                ->openUrlInNewTab(),

            Action::make('pemindahbukuan')
                ->label('Pemindahbukuan')
                ->icon('heroicon-o-building-library')
                ->color('gray')
                ->url(fn () => route('gaj.export.pemindahbukuan', $this->record))
                ->openUrlInNewTab(),
        ];
    }
}
