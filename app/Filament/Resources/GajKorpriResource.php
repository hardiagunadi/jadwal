<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GajKorpriResource\Pages\CreateGajKorpri;
use App\Filament\Resources\GajKorpriResource\Pages\ListGajKorpris;
use App\Models\GajKorpri;
use App\Models\GajPegawai;
use App\Models\SeksiModul;
use App\Support\RoleAccess;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class GajKorpriResource extends Resource
{
    protected static ?string $model = GajKorpri::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel = 'DKK Korpri';
    protected static ?string $pluralModelLabel = 'DKK Korpri';
    protected static ?string $modelLabel = 'DKK Korpri';
    protected static ?string $slug = 'gajs/korpri';
    protected static string|UnitEnum|null $navigationGroup = 'Penggajian';
    protected static ?int $navigationSort = 20;
    protected static string $modulKey = 'gaji';

    public static function form(Schema $schema): Schema
    {
        // Semua opsi PNS (NIP → Nama)
        $allPnsOptions = fn () => GajPegawai::query()
            ->whereHas('gaj', fn ($q) => $q->where('jenis', 'pns'))
            ->whereNotNull('nip')
            ->where('nip', '!=', '')
            ->select('nip', 'nama')
            ->orderBy('nama')
            ->get()
            ->unique('nip')
            ->pluck('nama', 'nip')
            ->toArray();

        // Opsi untuk multi-select create: kecualikan NIP yang sudah terdaftar
        $createOptions = fn () => GajPegawai::query()
            ->whereHas('gaj', fn ($q) => $q->where('jenis', 'pns'))
            ->whereNotNull('nip')
            ->where('nip', '!=', '')
            ->whereNotIn('nip', GajKorpri::pluck('nip'))
            ->select('nip', 'nama')
            ->orderBy('nama')
            ->get()
            ->unique('nip')
            ->pluck('nama', 'nip')
            ->toArray();

        return $schema->components([
            // Multi-select: hanya pada create, tidak disimpan langsung ke model
            Select::make('nips')
                ->label('Nama Pegawai (PNS)')
                ->required(fn (string $operation) => $operation === 'create')
                ->multiple()
                ->searchable()
                ->options($createOptions)
                ->hiddenOn('edit'),

            // Single select: hanya pada edit, disimpan ke kolom nip
            Select::make('nip')
                ->label('Nama Pegawai (PNS)')
                ->required(fn (string $operation) => $operation === 'edit')
                ->searchable()
                ->options($allPnsOptions)
                ->live()
                ->afterStateUpdated(function ($state, Set $set) {
                    $set('nama', $state ? (GajPegawai::where('nip', $state)->value('nama') ?? '') : '');
                })
                ->hiddenOn('create'),

            Hidden::make('nama'),

            TextInput::make('jumlah')
                ->label('Jumlah DKK Korpri (Rp)')
                ->required()
                ->numeric()
                ->default(20000)
                ->minValue(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nip')
                    ->label('NIP')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama Pegawai')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('jumlah')
                    ->label('Jumlah (Rp)')
                    ->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', '.'))
                    ->alignRight(),
            ])
            ->actions([
                EditAction::make()
                    ->form([
                        TextInput::make('jumlah')
                            ->label('Jumlah DKK Korpri (Rp)')
                            ->required()
                            ->numeric()
                            ->minValue(0),
                    ]),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListGajKorpris::route('/'),
            'create' => CreateGajKorpri::route('/create'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false; // Diakses via tombol di halaman Daftar Gaji
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        // Gunakan route key 'gajs' (sudah terdaftar di RoleAccess & ModuleSetting)
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
