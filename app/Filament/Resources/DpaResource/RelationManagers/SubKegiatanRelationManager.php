<?php

namespace App\Filament\Resources\DpaResource\RelationManagers;

use App\Models\DpaSubKegiatan;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class SubKegiatanRelationManager extends RelationManager
{
    protected static string $relationship = 'subKegiatans';

    protected static ?string $title = 'Sub Kegiatan';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('kode')
                ->label('Kode Sub Kegiatan')
                ->required()
                ->maxLength(50)
                ->placeholder('7.01.06.2.01.0014'),

            TextInput::make('nama')
                ->label('Nama Sub Kegiatan')
                ->required()
                ->maxLength(500)
                ->columnSpanFull(),

            TextInput::make('sumber_pendanaan')
                ->label('Sumber Pendanaan')
                ->maxLength(200)
                ->placeholder('PENDAPATAN ASLI DAERAH (PAD)'),

            TextInput::make('pagu')
                ->label('Pagu (Rp)')
                ->numeric()
                ->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode')
                    ->label('Kode')
                    ->searchable()
                    ->width('30%'),

                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama Sub Kegiatan')
                    ->searchable()
                    ->limit(60),

                Tables\Columns\TextColumn::make('sumber_pendanaan')
                    ->label('Sumber Dana')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('pagu')
                    ->label('Pagu (Rp)')
                    ->alignRight()
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((int) $state, 0, ',', '.')),

                Tables\Columns\TextColumn::make('total_spj')
                    ->label('Realisasi SPJ (Rp)')
                    ->alignRight()
                    ->getStateUsing(fn (DpaSubKegiatan $record) => $record->total_spj)
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((int) $state, 0, ',', '.')),

                Tables\Columns\TextColumn::make('sisa_anggaran')
                    ->label('Sisa Anggaran (Rp)')
                    ->alignRight()
                    ->getStateUsing(fn (DpaSubKegiatan $record) => $record->sisa_anggaran)
                    ->formatStateUsing(function ($state) {
                        $abs = 'Rp ' . number_format(abs((int) $state), 0, ',', '.');
                        return (int) $state < 0 ? '-' . $abs . ' ⚠' : $abs;
                    })
                    ->color(fn (DpaSubKegiatan $record) => $record->sisa_anggaran < 0 ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('rincian_belanjas_count')
                    ->label('Rincian')
                    ->counts('rincianBelanjas')
                    ->alignCenter(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn ($record) => blank($record) ? false : (method_exists($record, 'trashed') && $record->trashed())),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
