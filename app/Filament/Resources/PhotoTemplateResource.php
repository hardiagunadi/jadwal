<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PhotoTemplateResource\Pages;
use App\Models\PhotoTemplate;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class PhotoTemplateResource extends Resource
{
    protected static ?string $model = PhotoTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = 'Photo Templates';

    protected static ?string $modelLabel = 'Photo Template';

    protected static ?string $pluralModelLabel = 'Photo Templates';

    protected static string|UnitEnum|null $navigationGroup = 'Templates';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make('Template')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Template Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\FileUpload::make('base_image')
                            ->label('Frame Image (PNG)')
                            ->disk('public')
                            ->directory('photo-templates/frames')
                            ->image()
                            ->imageEditor()
                            ->acceptedFileTypes(['image/png'])
                            ->required(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Photo Slot')
                    ->description('Set where the uploaded photo should be placed on the frame.')
                    ->schema([
                        Forms\Components\TextInput::make('overlay_config.photo_slot.width')
                            ->numeric()
                            ->label('Width (px)')
                            ->required(),
                        Forms\Components\TextInput::make('overlay_config.photo_slot.height')
                            ->numeric()
                            ->label('Height (px)')
                            ->required(),
                        Forms\Components\TextInput::make('overlay_config.photo_slot.x')
                            ->numeric()
                            ->label('X offset (px)')
                            ->default(0)
                            ->required(),
                        Forms\Components\TextInput::make('overlay_config.photo_slot.y')
                            ->numeric()
                            ->label('Y offset (px)')
                            ->default(0)
                            ->required(),
                    ])
                    ->columns(4),
                Forms\Components\Section::make('Text Overlays')
                    ->description('Optional text layers that will be rendered on top of the photo.')
                    ->schema([
                        Forms\Components\Repeater::make('overlay_config.texts')
                            ->label('Texts')
                            ->default([])
                            ->schema([
                                Forms\Components\TextInput::make('label')
                                    ->label('Label/Content')
                                    ->required(),
                                Forms\Components\TextInput::make('x')
                                    ->numeric()
                                    ->label('X offset (px)')
                                    ->required(),
                                Forms\Components\TextInput::make('y')
                                    ->numeric()
                                    ->label('Y offset (px)')
                                    ->required(),
                                Forms\Components\TextInput::make('font_size')
                                    ->numeric()
                                    ->label('Font Size (px)')
                                    ->default(32),
                                Forms\Components\TextInput::make('color')
                                    ->label('Hex Color')
                                    ->placeholder('#000000')
                                    ->default('#000000'),
                                Forms\Components\TextInput::make('align')
                                    ->label('Alignment')
                                    ->placeholder('left, center, right')
                                    ->default('left'),
                                Forms\Components\FileUpload::make('font_path')
                                    ->label('Optional Font File (ttf, otf)')
                                    ->disk('public')
                                    ->directory('photo-templates/fonts')
                                    ->acceptedFileTypes(['font/ttf', 'font/otf', 'application/x-font-ttf', 'application/x-font-otf']),
                            ])
                            ->columns(2)
                            ->addActionLabel('Add Text Overlay'),
                    ]),
                Forms\Components\Section::make('Logos & Icons')
                    ->description('Upload logos or badges to place on the frame.')
                    ->schema([
                        Forms\Components\Repeater::make('overlay_config.logos')
                            ->label('Logos')
                            ->default([])
                            ->schema([
                                Forms\Components\FileUpload::make('path')
                                    ->label('Logo File')
                                    ->disk('public')
                                    ->directory('photo-templates/logos')
                                    ->image()
                                    ->imageEditor()
                                    ->required(),
                                Forms\Components\TextInput::make('width')
                                    ->numeric()
                                    ->label('Width (px)')
                                    ->required(),
                                Forms\Components\TextInput::make('height')
                                    ->numeric()
                                    ->label('Height (px)')
                                    ->required(),
                                Forms\Components\TextInput::make('x')
                                    ->numeric()
                                    ->label('X offset (px)')
                                    ->required(),
                                Forms\Components\TextInput::make('y')
                                    ->numeric()
                                    ->label('Y offset (px)')
                                    ->required(),
                            ])
                            ->columns(2)
                            ->addActionLabel('Add Logo'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\ImageColumn::make('base_image')
                    ->label('Frame')
                    ->disk('public'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPhotoTemplates::route('/'),
            'create' => Pages\CreatePhotoTemplate::route('/create'),
            'edit' => Pages\EditPhotoTemplate::route('/{record}/edit'),
        ];
    }
}
