<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DpaResource\Pages\CreateDpa;
use App\Filament\Resources\DpaResource\Pages\EditDpa;
use App\Filament\Resources\DpaResource\Pages\ListDpas;
use App\Filament\Resources\DpaResource\RelationManagers\RincianBelanjaRelationManager;
use App\Filament\Resources\DpaResource\RelationManagers\SubKegiatanRelationManager;
use App\Models\Dpa;
use App\Support\RoleAccess;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DpaResource extends Resource
{
    protected static ?string $model = Dpa::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationLabel = 'DPA';
    protected static ?string $pluralModelLabel = 'DPA';
    protected static ?string $modelLabel = 'DPA';
    protected static ?string $slug = 'dpa';
    protected static string|UnitEnum|null $navigationGroup = 'Seksi Ekbang';
    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas DPA')
                ->schema([
                    TextInput::make('nomor_dpa')
                        ->label('Nomor DPA')
                        ->required()
                        ->maxLength(200)
                        ->placeholder('DPA/A.1/7.01.0.00.0.00.05.0000/001/2026')
                        ->columnSpanFull(),

                    TextInput::make('tahun')
                        ->label('Tahun Anggaran')
                        ->numeric()
                        ->required()
                        ->default(now()->year),

                    TextInput::make('organisasi')
                        ->label('Organisasi / SKPD')
                        ->maxLength(300)
                        ->placeholder('7.01.0.00.0.00.05.0000 - Kecamatan Watumalang'),

                    TextInput::make('pagu_anggaran')
                        ->label('Pagu Anggaran (Rp)')
                        ->numeric()
                        ->default(0)
                        ->helperText('Total pagu seluruh sub kegiatan dalam DPA ini.'),
                ])
                ->columns(3),

            Section::make('Rincian Nomenklatur')
                ->schema([
                    TextInput::make('urusan')
                        ->label('Urusan Pemerintahan')
                        ->maxLength(300)
                        ->placeholder('7 - UNSUR KEWILAYAHAN'),

                    TextInput::make('bidang_urusan')
                        ->label('Bidang Urusan')
                        ->maxLength(300)
                        ->placeholder('7.01 - KECAMATAN'),

                    TextInput::make('program')
                        ->label('Program')
                        ->maxLength(500)
                        ->placeholder('7.01.06 - PROGRAM PEMBINAAN DAN PENGAWASAN...')
                        ->columnSpanFull(),

                    Textarea::make('kegiatan')
                        ->label('Kegiatan')
                        ->rows(2)
                        ->placeholder('7.01.06.2.01 - FASILITASI, REKOMENDASI DAN KOORDINASI...')
                        ->columnSpanFull(),

                    Textarea::make('catatan')
                        ->label('Catatan')
                        ->rows(2)
                        ->nullable()
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                Dpa::query()->withCount('subKegiatans')
            )
            ->defaultSort('tahun', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('nomor_dpa')
                    ->label('Nomor DPA')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('tahun')
                    ->label('Tahun')
                    ->sortable(),

                Tables\Columns\TextColumn::make('organisasi')
                    ->label('Organisasi / SKPD')
                    ->searchable()
                    ->limit(50)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('pagu_anggaran')
                    ->label('Pagu (Rp)')
                    ->alignRight()
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((int) $state, 0, ',', '.')),

                Tables\Columns\TextColumn::make('sub_kegiatans_count')
                    ->label('Sub Kegiatan')
                    ->alignCenter(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tahun')
                    ->label('Tahun')
                    ->options(function () {
                        return Dpa::query()
                            ->distinct()
                            ->orderByDesc('tahun')
                            ->pluck('tahun', 'tahun')
                            ->toArray();
                    }),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn ($record) => blank($record) ? false : (method_exists($record, 'trashed') && $record->trashed())),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->emptyStateHeading('Belum ada data DPA')
            ->emptyStateDescription('Tambahkan DPA menggunakan tombol di atas atau import dari PDF SIPD.');
    }

    public static function getRelationManagers(): array
    {
        return [
            SubKegiatanRelationManager::class,
            RincianBelanjaRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDpas::route('/'),
            'create' => CreateDpa::route('/create'),
            'edit' => EditDpa::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        if (! $user || ! RoleAccess::canSeeNav($user, 'filament.admin.resources.dpa')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $akronim = strtolower(trim((string) ($user->jabatan_akronim ?? '')));

        return $akronim === 'ekbang';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user || ! RoleAccess::canAccessRoute($user, 'filament.admin.resources.dpa')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $akronim = strtolower(trim((string) ($user->jabatan_akronim ?? '')));

        return $akronim === 'ekbang';
    }
}
