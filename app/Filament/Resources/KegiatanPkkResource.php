<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KegiatanPkkResource\Pages;
use App\Filament\Resources\Kegiatans\Schemas\KegiatanForm;
use App\Filament\Resources\Kegiatans\Tables\KegiatansTable;
use App\Models\Kegiatan;
use App\Models\SeksiModul;
use App\Support\RoleAccess;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class KegiatanPkkResource extends Resource
{
    protected static ?string $model = Kegiatan::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Agenda PKK Kecamatan';
    protected static ?string $pluralModelLabel = 'Agenda PKK Kecamatan';
    protected static ?string $modelLabel = 'Agenda PKK';
    protected static ?string $slug = 'kegiatans-pkk';
    protected static ?int $navigationSort = 16;
    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Kegiatan';
    protected static string $modulKey = 'agenda-pkk';

    public static function form(Schema $schema): Schema
    {
        return KegiatanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KegiatansTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('is_pkk', true);
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        if (! $user || ! RoleAccess::canSeeNav($user, 'filament.admin.resources.kegiatans-pkk')) {
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

        if (! $user || ! RoleAccess::canAccessRoute($user, 'filament.admin.resources.kegiatans-pkk')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $akronim = strtolower(trim((string) ($user->jabatan_akronim ?? '')));

        return SeksiModul::aktifUntuk($akronim, static::$modulKey);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKegiatanPkk::route('/'),
            'create' => Pages\CreateKegiatanPkk::route('/create'),
            'edit' => Pages\EditKegiatanPkk::route('/{record}/edit'),
        ];
    }
}
