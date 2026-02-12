<?php

namespace App\Filament\Resources\Kegiatans;

use App\Filament\Resources\Kegiatans\Pages\CreateKegiatan;
use App\Filament\Resources\Kegiatans\Pages\EditKegiatan;
use App\Filament\Resources\Kegiatans\Pages\ListKegiatans;
use App\Filament\Resources\Kegiatans\Schemas\KegiatanForm;
use App\Filament\Resources\Kegiatans\Tables\KegiatansTable;
use App\Models\Kegiatan;
use App\Models\SeksiModul;
use App\Support\RoleAccess;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;      // <-- PENTING, tambahkan ini
use BackedEnum;    // <-- sekalian untuk $navigationIcon (lihat di bawah)

class KegiatanResource extends Resource
{
    protected static ?string $model = Kegiatan::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Agenda Surat Masuk';
    protected static ?string $pluralModelLabel = 'Agenda Kegiatan';
    protected static ?string $modelLabel = 'Kegiatan';
    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Kegiatan';
    protected static string $modulKey = 'agenda';

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
            ->where(function (Builder $query) {
                $query->where('is_pkk', false)
                    ->orWhereNull('is_pkk');
            });
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        if (! $user || ! RoleAccess::canSeeNav($user, 'filament.admin.resources.kegiatans')) {
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

        if (! $user || ! RoleAccess::canAccessRoute($user, 'filament.admin.resources.kegiatans')) {
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
            'index'  => ListKegiatans::route('/'),
            'create' => CreateKegiatan::route('/create'),
            'edit'   => EditKegiatan::route('/{record}/edit'),
        ];
    }
}
