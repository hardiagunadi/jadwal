<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InstagramPostResource\Pages;
use App\Models\InstagramPost;
use App\Support\RoleAccess;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class InstagramPostResource extends Resource
{
    protected static ?string $model = InstagramPost::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = 'Instagram';

    protected static ?string $pluralModelLabel = 'Instagram Posts';

    protected static ?string $modelLabel = 'Instagram Post';

    protected static ?string $slug = 'instagram-posts';

    protected static string|UnitEnum|null $navigationGroup = 'Media Sosial';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Media')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('media_type')
                                    ->label('Jenis Media')
                                    ->options([
                                        'image' => 'Foto',
                                        'video' => 'Video',
                                    ])
                                    ->required(),
                                FileUpload::make('storage_path')
                                    ->label('Upload Foto/Video')
                                    ->disk('public')
                                    ->directory('instagram/posts')
                                    ->acceptedFileTypes(['image/*', 'video/*'])
                                    ->required(),
                            ]),
                        Toggle::make('generate_caption_via_ai')
                            ->label('Generate caption via AI')
                            ->helperText('Aktifkan untuk menghasilkan caption otomatis berdasarkan kata kunci.')
                            ->live()
                            ->dehydrated(false),
                    ]),
                Section::make('Caption')
                    ->schema([
                        Textarea::make('caption_prompt')
                            ->label('Kata kunci narasi')
                            ->placeholder('Contoh: Peluncuran program kebersihan kota')
                            ->rows(2),
                        Textarea::make('generated_caption')
                            ->label('Generated Caption')
                            ->rows(4)
                            ->helperText('Isi manual atau biarkan kosong jika ingin dihasilkan otomatis.')
                            ->autosize()
                            ->disabled(fn (Get $get) => (bool) $get('generate_caption_via_ai')),
                    ]),
                Section::make('Penjadwalan')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                DateTimePicker::make('scheduled_at')
                                    ->label('Jadwal Tayang')
                                    ->seconds(false),
                                Select::make('status')
                                    ->label('Status')
                                    ->options(InstagramPost::statusOptions())
                                    ->default(InstagramPost::STATUS_DRAFT)
                                    ->required(),
                            ]),
                        Select::make('template_id')
                            ->label('Template')
                            ->options(static::templateOptions())
                            ->searchable(),
                        Placeholder::make('template_preview')
                            ->label('Preview Template')
                            ->content(fn (Get $get) => static::templatePreview($get('template_id'))),
                        TextInput::make('ig_publish_id')
                            ->label('IG Publish ID')
                            ->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('media_type')
                    ->label('Media')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->sortable(),
                TextColumn::make('caption_prompt')
                    ->label('Kata Kunci')
                    ->limit(40)
                    ->wrap()
                    ->searchable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => InstagramPost::STATUS_DRAFT,
                        'info' => InstagramPost::STATUS_SCHEDULED,
                        'success' => InstagramPost::STATUS_PUBLISHED,
                        'danger' => InstagramPost::STATUS_FAILED,
                    ])
                    ->sortable(),
                TextColumn::make('scheduled_at')
                    ->label('Dijadwalkan')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('template_id')
                    ->label('Template')
                    ->formatStateUsing(fn ($state) => static::templateOptions()[$state] ?? 'Default')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(InstagramPost::statusOptions()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('schedulePublish')
                    ->label('Schedule publish')
                    ->icon('heroicon-m-clock')
                    ->color('primary')
                    ->form([
                        DateTimePicker::make('scheduled_at')
                            ->label('Jadwalkan pada')
                            ->default(now()->addHour())
                            ->required()
                            ->seconds(false),
                    ])
                    ->action(function (InstagramPost $record, array $data): void {
                        $record->update([
                            'scheduled_at' => $data['scheduled_at'],
                            'status' => InstagramPost::STATUS_SCHEDULED,
                        ]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return RoleAccess::canSeeNav(auth()->user(), 'filament.admin.resources.instagram-posts');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInstagramPosts::route('/'),
            'create' => Pages\CreateInstagramPost::route('/create'),
            'edit' => Pages\EditInstagramPost::route('/{record}/edit'),
        ];
    }

    protected static function templateOptions(): array
    {
        return [
            1 => 'Pengumuman Kegiatan',
            2 => 'Ucapan Selamat',
            3 => 'Informasi Layanan',
        ];
    }

    protected static function templatePreview($templateId): string
    {
        return match ($templateId) {
            1 => 'Sorotan poin-poin kegiatan dengan ajakan mengikuti acara.',
            2 => 'Caption pendek dengan emoji dan ucapan selamat.',
            3 => 'Template informatif dengan call-to-action ke tautan layanan.',
            default => 'Pilih template untuk melihat pratinjau.',
        };
    }
}
