<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GajKorpriResource\Pages\ListGajKorpris;
use App\Filament\Resources\GajKorpriResource\Pages\CreateGajKorpri;
use App\Filament\Resources\GajKorpriResource\Pages\EditGajKorpri;
use App\Models\GajKorpri;
use App\Models\SeksiModul;
use App\Support\RoleAccess;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
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
    protected static ?string $slug = 'gaj-korpris';
    protected static string|UnitEnum|null $navigationGroup = 'Penggajian';
    protected static ?int $navigationSort = 20;
    protected static string $modulKey = 'gaji';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nip')
                ->label('NIP')
                ->required()
                ->maxLength(20)
                ->unique(ignoreRecord: true),

            TextInput::make('nama')
                ->label('Nama Pegawai')
                ->maxLength(100),

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
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListGajKorpris::route('/'),
            'create' => CreateGajKorpri::route('/create'),
            'edit'   => EditGajKorpri::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        if (! $user || ! RoleAccess::canSeeNav($user, 'filament.admin.resources.gaj-korpris')) {
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

        if (! $user || ! RoleAccess::canAccessRoute($user, 'filament.admin.resources.gaj-korpris')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $akronim = strtolower(trim((string) ($user->jabatan_akronim ?? '')));

        return SeksiModul::aktifUntuk($akronim, static::$modulKey);
    }
}
