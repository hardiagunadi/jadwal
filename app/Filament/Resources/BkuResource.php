<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BkuResource\Pages\EditBku;
use App\Filament\Resources\BkuResource\Pages\ListBkus;
use App\Filament\Resources\BkuResource\Pages\ViewBku;
use App\Filament\Resources\BkuResource\RelationManagers\SpjsRelationManager;
use App\Models\Bku;
use App\Models\Dpa;
use App\Models\DpaSubKegiatan;
use App\Models\SeksiModul;
use App\Support\RoleAccess;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class BkuResource extends Resource
{
    protected static ?string $model = Bku::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'Daftar BKU';
    protected static ?string $pluralModelLabel = 'Daftar BKU';
    protected static ?string $modelLabel = 'BKU';
    protected static ?string $slug = 'bku';
    protected static ?int $navigationSort = 26;
    protected static string $modulKey = 'spj';

    public static function getNavigationGroup(): ?string
    {
        $user = auth()->user();
        if (! $user || $user->isAdmin()) {
            return 'Admin';
        }

        $akronim = strtolower(trim((string) ($user->jabatan_akronim ?? '')));

        return SeksiModul::labelSeksi($akronim) ?? strtoupper($akronim);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data BKU')
                ->schema([
                    Select::make('dpa_sub_kegiatan_id')
                        ->label('Sub Kegiatan DPA')
                        ->options(function () {
                            $query = DpaSubKegiatan::query()
                                ->with('dpa')
                                ->orderBy('kode');

                            $groups = [];
                            foreach ($query->get() as $sub) {
                                $dpa = $sub->dpa;
                                $groupKey = $dpa
                                    ? ($dpa->seksi_akronim ? '[' . strtoupper($dpa->seksi_akronim) . '] ' : '') . $dpa->nama . ' (' . $dpa->tahun . ')'
                                    : 'DPA Tidak Diketahui';
                                $groups[$groupKey][$sub->id] = '[' . $sub->kode . '] ' . $sub->nama;
                            }

                            return $groups;
                        })
                        ->searchable()
                        ->required()
                        ->columnSpanFull(),

                    Textarea::make('pekerjaan')
                        ->label('Pekerjaan')
                        ->rows(2)
                        ->helperText('Deskripsi pekerjaan untuk header BKU.')
                        ->columnSpanFull(),

                    Select::make('bulan')
                        ->label('Bulan')
                        ->options([
                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
                            4 => 'April', 5 => 'Mei', 6 => 'Juni',
                            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
                            10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                        ])
                        ->required(),

                    TextInput::make('tahun')
                        ->label('Tahun')
                        ->numeric()
                        ->required()
                        ->default(now()->year),

                    Textarea::make('catatan')
                        ->label('Catatan')
                        ->rows(2)
                        ->nullable(),
                ])
                ->columns(2),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data BKU')
                ->schema([
                    TextEntry::make('nama_label')
                        ->label('Nama BKU'),
                    TextEntry::make('dpaSubKegiatan.label')
                        ->label('Sub Kegiatan'),
                    TextEntry::make('pekerjaan')
                        ->label('Pekerjaan'),
                    TextEntry::make('bulan')
                        ->label('Bulan')
                        ->formatStateUsing(fn ($state) => [
                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
                            4 => 'April', 5 => 'Mei', 6 => 'Juni',
                            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
                            10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                        ][$state] ?? $state),
                    TextEntry::make('tahun')
                        ->label('Tahun'),
                    TextEntry::make('penerimaan')
                        ->label('Penerimaan (Rp)')
                        ->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', '.')),
                    TextEntry::make('total_pengeluaran')
                        ->label('Total Pengeluaran (Rp)')
                        ->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', '.')),
                    TextEntry::make('saldo')
                        ->label('Saldo (Rp)')
                        ->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', '.')),
                    TextEntry::make('catatan')
                        ->label('Catatan')
                        ->placeholder('-'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        $money = fn ($state) => number_format((int) $state, 0, ',', '.');

        return $table
            ->query(Bku::query()->with('dpaSubKegiatan')->withCount('spjs'))
            ->defaultSort('nomor_urut', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('nomor_urut')
                    ->label('No')
                    ->sortable(),

                Tables\Columns\TextColumn::make('nama_label')
                    ->label('Nama BKU')
                    ->searchable(['nomor_urut']),

                Tables\Columns\TextColumn::make('dpaSubKegiatan.kode')
                    ->label('Sub Kegiatan')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('bulan')
                    ->label('Bulan')
                    ->formatStateUsing(fn ($state) => [
                        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
                        5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
                        9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
                    ][$state] ?? $state),

                Tables\Columns\TextColumn::make('tahun')
                    ->label('Tahun')
                    ->sortable(),

                Tables\Columns\TextColumn::make('penerimaan')
                    ->label('Penerimaan')
                    ->alignRight()
                    ->formatStateUsing($money),

                Tables\Columns\TextColumn::make('spjs_count')
                    ->label('Jml SPJ')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('total_pengeluaran')
                    ->label('Pengeluaran')
                    ->alignRight()
                    ->formatStateUsing($money),

                Tables\Columns\TextColumn::make('saldo')
                    ->label('Saldo')
                    ->alignRight()
                    ->formatStateUsing($money)
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tahun')
                    ->label('Tahun')
                    ->options(fn () => Bku::query()->distinct()->orderByDesc('tahun')->pluck('tahun', 'tahun')->toArray()),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->emptyStateHeading('Belum ada data BKU')
            ->emptyStateDescription('Buat BKU baru atau generate dari daftar SPJ.');
    }

    public static function getRelations(): array
    {
        return [
            SpjsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBkus::route('/'),
            'view' => ViewBku::route('/{record}'),
            'edit' => EditBku::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        if (! $user || ! RoleAccess::canSeeNav($user, 'filament.admin.resources.bku')) {
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

        if (! $user || ! RoleAccess::canAccessRoute($user, 'filament.admin.resources.bku')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $akronim = strtolower(trim((string) ($user->jabatan_akronim ?? '')));

        return SeksiModul::aktifUntuk($akronim, static::$modulKey);
    }
}
