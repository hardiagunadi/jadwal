<?php

namespace App\Filament\Resources\BkuResource\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class SpjsRelationManager extends RelationManager
{
    protected static string $relationship = 'spjs';

    protected static ?string $title = 'Daftar SPJ';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        $money = fn ($state) => number_format((int) $state, 0, ',', '.');

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nomor_kwitansi')
                    ->label('Nomor Kwitansi')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('uraian')
                    ->label('Uraian')
                    ->limit(50)
                    ->searchable(),

                Tables\Columns\TextColumn::make('dpaRincianBelanja.kode_rekening')
                    ->label('Kode Rekening')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('jumlah')
                    ->label('Jumlah (Rp)')
                    ->alignRight()
                    ->formatStateUsing($money),

                Tables\Columns\TextColumn::make('total_pajak')
                    ->label('Pajak (Rp)')
                    ->alignRight()
                    ->formatStateUsing($money),

                Tables\Columns\TextColumn::make('jumlah_bersih')
                    ->label('Bersih (Rp)')
                    ->alignRight()
                    ->formatStateUsing($money)
                    ->color('success'),
            ])
            ->defaultSort('tanggal')
            ->headerActions([
                AttachAction::make()
                    ->label('Tambah SPJ')
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(fn ($query) => $query
                        ->whereDoesntHave('bkus')
                        ->orderByDesc('tanggal'))
                    ->after(fn () => $this->getOwnerRecord()->syncPenerimaan()),
            ])
            ->actions([
                DetachAction::make()
                    ->label('Keluarkan')
                    ->after(fn () => $this->getOwnerRecord()->syncPenerimaan()),
            ])
            ->bulkActions([
                DetachBulkAction::make()
                    ->after(fn () => $this->getOwnerRecord()->syncPenerimaan()),
            ]);
    }
}
