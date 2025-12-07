<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InstagramTemplateResource\Pages;
use App\Models\InstagramTemplate;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;
use BackedEnum;

class InstagramTemplateResource extends Resource
{
    protected static ?string $model = InstagramTemplate::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static UnitEnum|string|null $navigationGroup = 'Instagram';

    protected static ?string $navigationLabel = 'Frame Instagram';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('name')
                        ->required()
                        ->label('Nama Frame')
                        ->columnSpanFull(),
                ]),
                Grid::make(2)->schema([
                    TextInput::make('canvas_width')
                        ->numeric()
                        ->label('Lebar Canvas (px)')
                        ->helperText('Opsional. Biarkan kosong untuk mengikuti ukuran frame.'),
                    TextInput::make('canvas_height')
                        ->numeric()
                        ->label('Tinggi Canvas (px)')
                        ->helperText('Opsional. Biarkan kosong untuk mengikuti ukuran frame.'),
                ]),
                FileUpload::make('frame_path')
                    ->label('File Frame (PNG transparan)')
                    ->directory('instagram/templates')
                    ->image()
                    ->imageEditor()
                    ->preserveFilenames()
                    ->required(),
                Textarea::make('description')
                    ->label('Catatan')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('frame_path')
                    ->label('Preview')
                    ->disk('public')
                    ->height(80),
                TextColumn::make('name')->searchable(),
                TextColumn::make('canvas_width')->label('Lebar'),
                TextColumn::make('canvas_height')->label('Tinggi'),
                TextColumn::make('created_at')->dateTime('d M Y H:i'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageInstagramTemplates::route('/'),
        ];
    }
}
