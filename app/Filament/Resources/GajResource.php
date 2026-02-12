<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GajResource\Pages\ListGajs;
use App\Filament\Resources\GajResource\Pages\ViewGaj;
use App\Filament\Resources\GajResource\RelationManagers\GajPegawaisRelationManager;
use App\Models\Gaj;
use App\Models\SeksiModul;
use App\Support\RoleAccess;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class GajResource extends Resource
{
    protected static ?string $model = Gaj::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Daftar Gaji';
    protected static ?string $pluralModelLabel = 'Daftar Gaji';
    protected static ?string $modelLabel = 'Daftar Gaji';
    protected static ?string $slug = 'gajs';
    protected static string|UnitEnum|null $navigationGroup = 'Penggajian';
    protected static ?int $navigationSort = 10;
    protected static string $modulKey = 'gaji';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Daftar Gaji')
                ->columns(3)
                ->components([
                    TextEntry::make('jenis')
                        ->label('Jenis')
                        ->badge()
                        ->color(fn ($state) => $state === 'pns' ? 'primary' : 'warning')
                        ->formatStateUsing(fn ($state) => strtoupper($state)),

                    TextEntry::make('periode')
                        ->label('Periode'),

                    TextEntry::make('nama_satker')
                        ->label('Satuan Kerja'),

                    TextEntry::make('kode_satker')
                        ->label('Kode Satker')
                        ->placeholder('-'),

                    TextEntry::make('seksi_akronim')
                        ->label('Seksi')
                        ->badge()
                        ->color('info')
                        ->formatStateUsing(fn ($state) => strtoupper((string) $state))
                        ->placeholder('-'),

                    TextEntry::make('total_bersih')
                        ->label('Total Bersih')
                        ->formatStateUsing(fn ($state) => 'Rp ' . number_format((int) $state, 0, ',', '.')),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('jenis')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn ($state) => $state === 'pns' ? 'primary' : 'warning')
                    ->formatStateUsing(fn ($state) => strtoupper($state)),

                Tables\Columns\TextColumn::make('nama_satker')
                    ->label('Satker')
                    ->searchable(),

                Tables\Columns\TextColumn::make('bulan')
                    ->label('Periode')
                    ->formatStateUsing(fn ($state, Gaj $record) => $record->periode)
                    ->sortable(),

                Tables\Columns\TextColumn::make('tahun')
                    ->label('Tahun')
                    ->sortable(),

                Tables\Columns\TextColumn::make('seksi_akronim')
                    ->label('Seksi')
                    ->badge()
                    ->formatStateUsing(fn ($state) => strtoupper((string) $state))
                    ->color('info')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('pegawais_count')
                    ->label('Jml Pegawai')
                    ->counts('pegawais')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('total_bersih')
                    ->label('Total Bersih')
                    ->getStateUsing(fn (Gaj $record) => $record->pegawais()->sum('jumlah_bersih'))
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->alignRight(),
            ])
            ->defaultSort('tahun', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('jenis')
                    ->label('Jenis')
                    ->options(['pns' => 'PNS', 'pppk' => 'PPPK']),

                Tables\Filters\SelectFilter::make('tahun')
                    ->label('Tahun')
                    ->options(fn () => Gaj::query()
                        ->distinct()
                        ->orderByDesc('tahun')
                        ->pluck('tahun', 'tahun')
                        ->toArray()),
            ])
            ->actions([
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            GajPegawaisRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGajs::route('/'),
            'view'  => ViewGaj::route('/{record}'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        if (! $user || ! RoleAccess::canSeeNav($user, 'filament.admin.resources.gajs')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $akronim = strtolower(trim((string) ($user->jabatan_akronim ?? '')));

        return SeksiModul::aktifUntuk($akronim, static::$modulKey);
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user || ! RoleAccess::canAccessRoute($user, 'filament.admin.resources.gajs')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $akronim = strtolower(trim((string) ($user->jabatan_akronim ?? '')));

        return SeksiModul::aktifUntuk($akronim, static::$modulKey);
    }
}
