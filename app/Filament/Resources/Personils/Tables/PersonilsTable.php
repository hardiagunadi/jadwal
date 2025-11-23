<?php

namespace App\Filament\Resources\Personils\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PersonilsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('jabatan')
                    ->label('Jabatan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('no_wa')
                    ->label('Nomor WA')
                    ->searchable(),

                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(40)
                    ->tooltip(fn ($state) => $state),
            ])
            ->filters([
                //
            ])
            // ==== AKSI PER BARIS (EDIT DLL) ====
            ->recordActions([
                EditAction::make(), // edit dalam modal / halaman edit resource
                // Kalau mau tambah action lain di sini juga bisa
            ])
            // ==== AKSI TOOLBAR (BULK DELETE DLL) ====
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
