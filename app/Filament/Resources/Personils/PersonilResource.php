<?php

namespace App\Filament\Resources\Personils;

use App\Filament\Resources\Personils\Pages\CreatePersonil;
use App\Filament\Resources\Personils\Pages\EditPersonil;
use App\Filament\Resources\Personils\Pages\ListPersonils;
use App\Filament\Resources\Personils\Schemas\PersonilForm;
use App\Filament\Resources\Personils\Tables\PersonilsTable;
use App\Models\Personil;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;      // <-- PENTING, tambahkan ini
use BackedEnum;    // <-- sekalian untuk $navigationIcon (lihat di bawah)

class PersonilResource extends Resource
{
    protected static ?string $model = Personil::class;

	 // Sesuaikan type dengan Filament v4:
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationLabel = 'Personil';
    protected static ?string $pluralModelLabel = 'Personil';
    protected static ?string $modelLabel = 'Personil';
	
    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Kegiatan';

    public static function form(Schema $schema): Schema
    {
        return PersonilForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PersonilsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListPersonils::route('/'),
            'create' => CreatePersonil::route('/create'),
            'edit'   => EditPersonil::route('/{record}/edit'),
        ];
    }
}
