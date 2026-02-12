<?php

namespace App\Filament\Resources\DpaResource\RelationManagers;

use App\Models\DpaRincianBelanja;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class RincianBelanjaRelationManager extends RelationManager
{
    protected static string $relationship = 'rincianBelanjas';

    protected static ?string $title = 'Rincian Belanja & Realisasi';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode_rekening')
                    ->label('Kode Rekening')
                    ->searchable()
                    ->width('20%'),

                Tables\Columns\TextColumn::make('uraian')
                    ->label('Uraian')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('jumlah')
                    ->label('Pagu (Rp)')
                    ->alignRight()
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((int) $state, 0, ',', '.')),

                Tables\Columns\TextColumn::make('total_spj')
                    ->label('Realisasi (Rp)')
                    ->alignRight()
                    ->getStateUsing(fn (DpaRincianBelanja $record) => $record->total_spj)
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((int) $state, 0, ',', '.')),

                Tables\Columns\TextColumn::make('sisa_anggaran')
                    ->label('Sisa (Rp)')
                    ->alignRight()
                    ->getStateUsing(fn (DpaRincianBelanja $record) => $record->sisa_anggaran)
                    ->formatStateUsing(function ($state) {
                        $abs = 'Rp ' . number_format(abs((int) $state), 0, ',', '.');
                        return (int) $state < 0 ? '-' . $abs . ' ⚠' : $abs;
                    })
                    ->color(fn (DpaRincianBelanja $record) => $record->sisa_anggaran < 0 ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('persen_realisasi')
                    ->label('%')
                    ->alignCenter()
                    ->getStateUsing(fn (DpaRincianBelanja $record) => $record->jumlah > 0
                        ? round($record->total_spj / $record->jumlah * 100, 1)
                        : 0
                    )
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->color(fn (DpaRincianBelanja $record) => match (true) {
                        $record->jumlah <= 0 => 'gray',
                        ($record->total_spj / $record->jumlah) > 1 => 'danger',
                        ($record->total_spj / $record->jumlah) >= 0.8 => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('spjs_count')
                    ->label('SPJ')
                    ->counts('spjs')
                    ->alignCenter(),
            ])
            ->defaultSort('kode_rekening');
    }
}
