<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SuratKeluarResource\Pages\CreateSuratKeluar;
use App\Filament\Resources\SuratKeluarResource\Pages\EditSuratKeluar;
use App\Filament\Resources\SuratKeluarResource\Pages\ListSuratKeluars;
use App\Models\KodeSurat;
use App\Models\Personil;
use App\Models\SuratKeluar;
use App\Models\SeksiModul;
use App\Support\RoleAccess;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use UnitEnum;

class SuratKeluarResource extends Resource
{
    protected static ?string $model = SuratKeluar::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-envelope';
    protected static ?string $navigationLabel = 'Surat Keluar';
    protected static ?string $pluralModelLabel = 'Surat Keluar';
    protected static ?string $modelLabel = 'Surat Keluar';
    protected static ?string $slug = 'surat-keluar';
    protected static string|UnitEnum|null $navigationGroup = 'Administrasi Surat';
    protected static ?int $navigationSort = 15;
    protected static string $modulKey = 'surat-keluar';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data Surat Keluar')
                ->schema([
                    Select::make('jenis_nomor')
                        ->label('Jenis Nomor')
                        ->options([
                            'master' => 'Nomor baru',
                            'sisipan' => 'Nomor sisipan',
                        ])
                        ->default('master')
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state === 'master') {
                                $set('master_id', null);
                            }
                        })
                        ->hiddenOn('edit'),

                    Select::make('kode_surat_id')
                        ->label(fn (callable $get) => ($get('jenis_nomor') ?? 'master') === 'master'
                            ? 'Kode Klasifikasi'
                            : 'Kode Klasifikasi (opsional, kosongkan untuk ikut master)')
                        ->options(fn () => KodeSurat::query()
                            ->orderBy('kode')
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn (KodeSurat $kode) => [
                                $kode->id => $kode->kode . ' - ' . $kode->keterangan,
                            ])
                            ->all())
                        ->getSearchResultsUsing(function (string $search): array {
                            $search = trim($search);

                            return KodeSurat::query()
                                ->when($search !== '', function ($query) use ($search) {
                                    $query->where(function ($builder) use ($search) {
                                        $builder
                                            ->where('kode', 'like', '%' . $search . '%')
                                            ->orWhere('keterangan', 'like', '%' . $search . '%');
                                    });
                                })
                                ->orderBy('kode')
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(fn (KodeSurat $kode) => [
                                    $kode->id => $kode->kode . ' - ' . $kode->keterangan,
                                ])
                                ->all();
                        })
                        ->getOptionLabelUsing(function ($value): ?string {
                            if (! $value) {
                                return null;
                            }

                            $kode = KodeSurat::find($value);
                            if (! $kode) {
                                return null;
                            }

                            return $kode->kode . ' - ' . $kode->keterangan;
                        })
                        ->searchable()
                        ->preload()
                        ->placeholder('Ketik kode atau keterangan')
                        ->required(fn (callable $get) => ($get('jenis_nomor') ?? 'master') === 'master')
                        ->live()
                        ->hiddenOn('edit')
                        ->createOptionForm(fn () => auth()->user()?->isAdmin() ? [
                            TextInput::make('kode')
                                ->label('Kode Klasifikasi')
                                ->required()
                                ->maxLength(50),
                            TextInput::make('keterangan')
                                ->label('Keterangan')
                                ->required()
                                ->maxLength(255),
                        ] : [])
                        ->createOptionUsing(function (array $data): int {
                            return KodeSurat::create($data)->id;
                        }),

                    Select::make('master_id')
                        ->label('Nomor Master')
                        ->options(fn () => SuratKeluar::query()
                            ->where('nomor_sisipan', 0)
                            ->orderByDesc('created_at')
                            ->with('kodeSurat')
                            ->get()
                            ->mapWithKeys(fn (SuratKeluar $surat) => [
                                $surat->id => $surat->nomor_label . ' · ' . $surat->perihal,
                            ]))
                        ->searchable()
                        ->preload()
                        ->required(fn (callable $get) => ($get('jenis_nomor') ?? 'master') === 'sisipan')
                        ->live()
                        ->visible(fn (callable $get) => ($get('jenis_nomor') ?? 'master') === 'sisipan')
                        ->hiddenOn('edit'),

                    Textarea::make('perihal')
                        ->label('Hal / Perihal')
                        ->rows(3)
                        ->required(),

                    DatePicker::make('tanggal_surat')
                        ->label('Tanggal Surat')
                        ->required()
                        ->default(now())
                        ->native(false)
                        ->displayFormat('d/m/Y'),

                    Select::make('requested_by_personil_id')
                        ->label('Akronim Jabatan')
                        ->options(fn () => Personil::query()
                            ->whereNotNull('jabatan_akronim')
                            ->where('jabatan_akronim', '!=', '')
                            ->orderBy('jabatan_akronim')
                            ->limit(100)
                            ->get()
                            ->mapWithKeys(function (Personil $personil) {
                                $akronim = trim((string) ($personil->jabatan_akronim ?? ''));
                                $nama = trim((string) ($personil->nama ?? ''));

                                if ($nama !== '') {
                                    return [$personil->id => $akronim . ' - ' . $nama];
                                }

                                return [$personil->id => $akronim];
                            })
                            ->all())
                        ->getSearchResultsUsing(function (string $search): array {
                            $term = trim($search);

                            return Personil::query()
                                ->whereNotNull('jabatan_akronim')
                                ->where('jabatan_akronim', '!=', '')
                                ->when($term !== '', function ($query) use ($term) {
                                    $query->where(function ($builder) use ($term) {
                                        $builder
                                            ->where('jabatan_akronim', 'like', "%{$term}%")
                                            ->orWhere('nama', 'like', "%{$term}%")
                                            ->orWhere('jabatan', 'like', "%{$term}%");
                                    });
                                })
                                ->orderBy('jabatan_akronim')
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(function (Personil $personil) {
                                    $akronim = trim((string) ($personil->jabatan_akronim ?? ''));
                                    $nama = trim((string) ($personil->nama ?? ''));

                                    if ($nama !== '') {
                                        return [$personil->id => $akronim . ' - ' . $nama];
                                    }

                                    return [$personil->id => $akronim];
                                })
                                ->all();
                        })
                        ->getOptionLabelUsing(function ($value): ?string {
                            if (! $value) {
                                return null;
                            }

                            $personil = Personil::find($value);
                            if (! $personil) {
                                return null;
                            }

                            $akronim = trim((string) ($personil->jabatan_akronim ?? ''));
                            if ($akronim === '') {
                                return null;
                            }

                            $nama = trim((string) ($personil->nama ?? ''));

                            if ($nama !== '') {
                                return $akronim . ' - ' . $nama;
                            }

                            return $akronim;
                        })
                        ->searchable()
                        ->preload()
                        ->placeholder('Pilih akronim')
                        ->helperText('Akronim akan ditambahkan di akhir nomor surat.')
                        ->default(function () {
                            $user = auth()->user();

                            if ($user?->isInFinishWhitelist() !== true) {
                                return null;
                            }

                            $akronim = trim((string) ($user->jabatan_akronim ?? ''));

                            return $akronim !== '' ? $user->id : null;
                        })
                        ->required(fn () => auth()->user()?->isInFinishWhitelist() === true)
                        ->live()
                        ->visible(fn () => auth()->user()?->isInFinishWhitelist() === true),

                    Placeholder::make('nomor_preview')
                        ->label('Preview Nomor Surat')
                        ->content(function (callable $get) {
                            $label = app(\App\Services\SuratKeluarService::class)
                                ->previewNextMasterNumberText($get('kode_surat_id'), $get('master_id'));
                            $personilId = $get('requested_by_personil_id');

                            if (! $personilId) {
                                return $label;
                            }

                            $personil = Personil::find($personilId);
                            $akronim = trim((string) ($personil?->jabatan_akronim ?? ''));

                            if ($akronim === '') {
                                return $label;
                            }

                            return $label . '/' . $akronim;
                        })
                        ->visible(fn (callable $get) => filled($get('kode_surat_id')) || filled($get('master_id')))
                        ->hiddenOn('edit')
                        ->dehydrated(false),
                ])
                ->columns(1)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nomor_label')
                    ->label('Nomor Surat')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($state, SuratKeluar $record) {
                        $label = (string) $state;
                        $akronim = trim((string) ($record->requester?->jabatan_akronim ?? ''));

                        if ($akronim === '') {
                            return $label;
                        }

                        return $label . '/' . $akronim;
                    }),
                Tables\Columns\TextColumn::make('perihal')
                    ->label('Perihal')
                    ->limit(40)
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => $state === 'booked' ? 'Nomor sudah dibooking' : 'Terbit')
                    ->colors([
                        'warning' => 'booked',
                        'success' => 'issued',
                    ])
                    ->icons([
                        'booked' => 'heroicon-m-bookmark',
                        'issued' => 'heroicon-m-check-badge',
                    ]),
                Tables\Columns\TextColumn::make('tanggal_surat')
                    ->label('Tanggal Surat')
                    ->formatStateUsing(fn ($state) => $state?->format('d/m/Y') ?? '-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('booked_at')
                    ->label('Tanggal Booking')
                    ->formatStateUsing(fn ($state) => $state?->format('d/m/Y') ?? '-')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('kodeSurat.kode')
                    ->label('Kode')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('source')
                    ->label('Sumber')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
                BulkAction::make('surat_pengantar')
                    ->label('Generate Surat')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->deselectRecordsAfterCompletion()
                    ->form(function () {
                        $jabatanCamat = env('JADWAL_JABATAN_CAMAT', 'Camat Watumalang');
                        $camat = Personil::where('jabatan', $jabatanCamat)->first();

                        return [
                            Select::make('jenis_surat_pilihan')
                                ->label('Jenis Surat')
                                ->options([
                                    'Surat Pengantar'   => 'Surat Pengantar',
                                    'Nota Dinas'        => 'Nota Dinas',
                                    'Surat Keterangan'  => 'Surat Keterangan',
                                    'Surat Permohonan'  => 'Surat Permohonan',
                                    'lainnya'           => 'Lainnya (ketik sendiri)…',
                                ])
                                ->default('Surat Pengantar')
                                ->required()
                                ->live(),

                            TextInput::make('jenis_surat_custom')
                                ->label('Jenis Surat (ketik manual)')
                                ->placeholder('Contoh: Surat Pengantar Berkas…')
                                ->visible(fn (callable $get) => $get('jenis_surat_pilihan') === 'lainnya')
                                ->required(fn (callable $get) => $get('jenis_surat_pilihan') === 'lainnya'),

                            Textarea::make('kepada')
                                ->label('Kepada (Yth.)')
                                ->placeholder('Bupati Wonosobo')
                                ->rows(2)
                                ->required(),

                            Textarea::make('isi_pengantar')
                                ->label('Isi Pengantar (Jenis Surat/Barang yang Dikirim)')
                                ->placeholder('Contoh: Laporan Keuangan Bulan Januari 2025')
                                ->rows(4)
                                ->required(),

                            Select::make('pegawai_ids')
                                ->label('Tambah Daftar Pegawai ke Isi Pengantar (Opsional)')
                                ->multiple()
                                ->options(fn () => Personil::query()
                                    ->orderBy('nama')
                                    ->get()
                                    ->mapWithKeys(fn (Personil $p) => [
                                        $p->id => $p->nama . ($p->nip ? ' – NIP. ' . $p->nip : ''),
                                    ])
                                    ->all())
                                ->searchable()
                                ->helperText('Jika dipilih, nama dan NIP pegawai akan ditambahkan ke teks isi pengantar.')
                                ->nullable(),

                            DatePicker::make('tanggal')
                                ->label('Tanggal Surat')
                                ->default(now())
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->helperText('Otomatis terisi hari ini. Ubah jika perlu.'),

                            Placeholder::make('info_pengirim')
                                ->label('Informasi Pengirim (Otomatis dari sistem)')
                                ->content(function () use ($jabatanCamat, $camat) {
                                    return implode('  |  ', [
                                        'Jabatan: ' . $jabatanCamat,
                                        'Nama: ' . ($camat?->nama ?? '–'),
                                        'Pangkat/Gol: ' . ($camat?->pangkat ?? '–') . ' / ' . ($camat?->golongan ?? '–'),
                                        'NIP: ' . ($camat?->nip ?? '–'),
                                    ]);
                                }),
                        ];
                    })
                    ->action(function (Collection $records, array $data) {
                        $jabatanCamat = env('JADWAL_JABATAN_CAMAT', 'Camat Watumalang');
                        $camat        = Personil::where('jabatan', $jabatanCamat)->first();

                        // Jenis surat
                        $jenisSurat = $data['jenis_surat_pilihan'] === 'lainnya'
                            ? trim((string) ($data['jenis_surat_custom'] ?? 'Surat Pengantar'))
                            : $data['jenis_surat_pilihan'];

                        // Nomor surat dari semua record yang dipilih
                        $nomors = $records->map(function (SuratKeluar $record) {
                            $label   = $record->nomor_label;
                            $akronim = trim((string) ($record->requester?->jabatan_akronim ?? ''));

                            return $akronim ? $label . '/' . $akronim : $label;
                        })->implode(', ');

                        // Isi pengantar + daftar pegawai
                        $isiPengantar = trim((string) ($data['isi_pengantar'] ?? ''));
                        $pegawaiIds   = $data['pegawai_ids'] ?? [];

                        if (! empty($pegawaiIds)) {
                            $pegawais     = Personil::whereIn('id', $pegawaiIds)->orderBy('nama')->get();
                            $banyak       = $pegawais->count();
                            $pegawaiLines = $pegawais->values()->map(function (Personil $p, int $idx) use ($banyak) {
                                $nama  = $p->nama ?? '';
                                $nip   = $p->nip ? 'NIP. ' . $p->nip : '';

                                if ($banyak > 1) {
                                    // "1. Nama\n   NIP. ..."
                                    $prefix = ($idx + 1) . '. ';
                                    $indent = str_repeat(' ', strlen($prefix));
                                    return $nip !== ''
                                        ? $prefix . $nama . "\n" . $indent . $nip
                                        : $prefix . $nama;
                                }

                                return $nip !== '' ? $nama . "\n" . $nip : $nama;
                            })->implode("\n");

                            $isiPengantar = $isiPengantar !== ''
                                ? $isiPengantar . "\n" . $pegawaiLines
                                : $pegawaiLines;
                        }

                        // Tanggal
                        $tanggal = Carbon::parse($data['tanggal'] ?? now())
                            ->locale('id')
                            ->isoFormat('D MMMM Y');

                        // Simpan ke session
                        $key = Str::uuid()->toString();
                        session()->put('surat_pengantar_' . $key, [
                            'jenis_surat'  => $jenisSurat,
                            'kepada'       => $data['kepada'],
                            'isi_pengantar'=> $isiPengantar,
                            'tanggal'      => $tanggal,
                            'nomor_surat'  => $nomors,
                            'jabatan'      => $jabatanCamat,
                            'nama_camat'   => $camat?->nama   ?? '–',
                            'pangkat'      => $camat?->pangkat ?? '–',
                            'golongan'     => $camat?->golongan ?? '–',
                            'nip'          => $camat?->nip ?? '–',
                        ]);

                        session()->put('surat_pengantar_latest_key', $key);
                    })
                    ->successRedirectUrl(fn (): string => route('surat-pengantar.print', [
                        'key' => session('surat_pengantar_latest_key'),
                    ])),
            ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        if (! $user || ! RoleAccess::canSeeNav($user, 'filament.admin.resources.surat-keluar')) {
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

        if (! $user || ! RoleAccess::canAccessRoute($user, 'filament.admin.resources.surat-keluar')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $akronim = strtolower(trim((string) ($user->jabatan_akronim ?? '')));

        return SeksiModul::aktifUntuk($akronim, static::$modulKey);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('requester');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSuratKeluars::route('/'),
            'create' => CreateSuratKeluar::route('/create'),
            'edit' => EditSuratKeluar::route('/{record}/edit'),
        ];
    }
}
