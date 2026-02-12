<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SpjResource\Pages\CreateSpj;
use App\Filament\Resources\SpjResource\Pages\EditSpj;
use App\Filament\Resources\SpjResource\Pages\ListSpjs;
use App\Models\Dpa;
use App\Models\DpaRincianBelanja;
use App\Models\DpaSubKegiatan;
use App\Models\Personil;
use App\Models\Spj;
use App\Models\SuratKeluar;
use App\Services\TaxCalculationService;
use App\Support\RoleAccess;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use UnitEnum;

class SpjResource extends Resource
{
    protected static ?string $model = Spj::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';
    protected static ?string $navigationLabel = 'SPJ Kwitansi Dinas';
    protected static ?string $pluralModelLabel = 'SPJ Kwitansi Dinas';
    protected static ?string $modelLabel = 'SPJ';
    protected static ?string $slug = 'spj';
    protected static string|UnitEnum|null $navigationGroup = 'Seksi Ekbang';
    protected static ?int $navigationSort = 25;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data SPJ')
                ->schema([
                    Select::make('surat_keluar_id')
                        ->label('Surat Keluar')
                        ->options(function () {
                            return SuratKeluar::query()
                                ->with(['kodeSurat', 'requester'])
                                ->where('status', SuratKeluar::STATUS_ISSUED)
                                ->orderByDesc('id')
                                ->get()
                                ->mapWithKeys(function (SuratKeluar $sk) {
                                    $label = $sk->nomor_label;
                                    if ($sk->perihal) {
                                        $label .= ' – ' . $sk->perihal;
                                    }
                                    if ($sk->requester?->jabatan_akronim) {
                                        $label .= ' [' . strtoupper($sk->requester->jabatan_akronim) . ']';
                                    }
                                    return [$sk->id => $label];
                                });
                        })
                        ->searchable()
                        ->nullable()
                        ->helperText('Pilih surat keluar yang terkait, atau kosongkan jika tidak ada.'),

                    Select::make('dpa_rincian_belanja_id')
                        ->label('Rincian DPA (Sub Kegiatan / Kode Rekening)')
                        ->options(function () {
                            $allKodes = DpaRincianBelanja::query()
                                ->pluck('kode_rekening')
                                ->toArray();

                            $leafItems = DpaRincianBelanja::query()
                                ->with('subKegiatan.dpa')
                                ->orderBy('kode_rekening')
                                ->get()
                                ->filter(function (DpaRincianBelanja $r) use ($allKodes) {
                                    $prefix = $r->kode_rekening . '.';
                                    foreach ($allKodes as $other) {
                                        if (str_starts_with($other, $prefix)) {
                                            return false;
                                        }
                                    }
                                    return true;
                                });

                            $groups = [];
                            foreach ($leafItems as $r) {
                                $sub = $r->subKegiatan;
                                $dpa = $sub?->dpa;

                                $groupKey = $sub
                                    ? '[' . $sub->kode . '] ' . $sub->nama . ($dpa ? ' (' . $dpa->tahun . ')' : '')
                                    : 'Sub Kegiatan Tidak Diketahui';

                                $sisa = $r->sisa_anggaran;
                                $sisaStr = number_format(abs($sisa), 0, ',', '.');
                                $sisaLabel = $sisa >= 0 ? "Sisa: Rp {$sisaStr}" : "LEWAT: Rp {$sisaStr}";

                                $label = '[' . $r->kode_rekening . '] ' . $r->uraian
                                    . ' — Pagu: Rp ' . number_format((int) $r->jumlah, 0, ',', '.')
                                    . ' | ' . $sisaLabel;

                                $groups[$groupKey][$r->id] = $label;
                            }

                            return $groups;
                        })
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                            self::recalculateTax($state, (int) $get('jumlah'), $set);
                        })
                        ->nullable()
                        ->helperText('Hanya menampilkan kode rekening paling detail dari DPA yang sudah diimport.')
                        ->columnSpanFull(),

                    Placeholder::make('info_sisa_anggaran')
                        ->label('Informasi Anggaran')
                        ->content(function (Get $get): HtmlString {
                            $id = $get('dpa_rincian_belanja_id');
                            if (! $id) {
                                return new HtmlString('<span class="text-gray-400 text-sm">Pilih Rincian DPA untuk melihat informasi anggaran.</span>');
                            }

                            $r = DpaRincianBelanja::with('subKegiatan')->find($id);
                            if (! $r) {
                                return new HtmlString('<span class="text-gray-400 text-sm">Data tidak ditemukan.</span>');
                            }

                            $totalSpj = $r->total_spj;
                            $sisa = $r->sisa_anggaran;
                            $persen = $r->jumlah > 0 ? round($totalSpj / $r->jumlah * 100, 1) : 0;

                            $sisaColor = $sisa >= 0 ? 'text-green-600' : 'text-red-600';
                            $sisaLabel = $sisa >= 0
                                ? 'Rp ' . number_format($sisa, 0, ',', '.')
                                : '-Rp ' . number_format(abs($sisa), 0, ',', '.');

                            $sub = $r->subKegiatan;
                            $subInfo = $sub ? "<div class='text-xs text-gray-500 mb-1'>Sub Kegiatan: [{$sub->kode}] {$sub->nama}</div>" : '';

                            return new HtmlString(
                                $subInfo .
                                "<div class='grid grid-cols-3 gap-3 mt-1 text-sm'>" .
                                "<div class='bg-gray-50 rounded p-2'><div class='text-xs text-gray-500'>Pagu</div><div class='font-semibold'>Rp " . number_format((int) $r->jumlah, 0, ',', '.') . "</div></div>" .
                                "<div class='bg-yellow-50 rounded p-2'><div class='text-xs text-gray-500'>Terpakai ({$persen}%)</div><div class='font-semibold text-yellow-700'>Rp " . number_format($totalSpj, 0, ',', '.') . "</div></div>" .
                                "<div class='rounded p-2 " . ($sisa >= 0 ? 'bg-green-50' : 'bg-red-50') . "'><div class='text-xs text-gray-500'>Sisa</div><div class='font-semibold {$sisaColor}'>{$sisaLabel}</div></div>" .
                                "</div>"
                            );
                        })
                        ->columnSpanFull(),

                    Select::make('personil_id')
                        ->label('PPTK / Pemohon')
                        ->options(function () {
                            return Personil::query()
                                ->orderBy('jabatan_akronim')
                                ->orderBy('nama')
                                ->get()
                                ->mapWithKeys(fn (Personil $p) => [
                                    $p->id => $p->nama . ($p->jabatan_akronim ? ' [' . strtoupper($p->jabatan_akronim) . ']' : ''),
                                ]);
                        })
                        ->searchable()
                        ->nullable(),

                    TextInput::make('nomor_kwitansi')
                        ->label('Nomor Kwitansi')
                        ->placeholder('900/001/2026')
                        ->maxLength(100)
                        ->nullable(),

                    DatePicker::make('tanggal')
                        ->label('Tanggal')
                        ->required()
                        ->default(now()),

                    TextInput::make('tahun')
                        ->label('Tahun Anggaran')
                        ->numeric()
                        ->required()
                        ->default(now()->year),

                    TextInput::make('jumlah')
                        ->label('Jumlah Kotor (Rp)')
                        ->numeric()
                        ->nullable()
                        ->live(debounce: 600)
                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                            self::recalculateTax($get('dpa_rincian_belanja_id'), (int) $state, $set);
                        })
                        ->helperText('Masukkan angka tanpa titik/koma.')
                        ->rules([
                            fn (Get $get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                                if (! $value || ! $get('dpa_rincian_belanja_id')) {
                                    return;
                                }
                                $rincian = DpaRincianBelanja::find($get('dpa_rincian_belanja_id'));
                                if (! $rincian) {
                                    return;
                                }
                                $sisa = $rincian->sisa_anggaran;
                                if ((int) $value > $sisa) {
                                    $sisaFormat = 'Rp ' . number_format($sisa, 0, ',', '.');
                                    $fail("Jumlah melebihi sisa anggaran rekening ini ({$sisaFormat}).");
                                }
                            },
                        ]),

                    Textarea::make('uraian')
                        ->label('Uraian Pembayaran')
                        ->required()
                        ->rows(3)
                        ->helperText('Kalimat ini akan muncul sebagai keterangan pembayaran di kwitansi.'),

                    Textarea::make('catatan')
                        ->label('Catatan')
                        ->rows(2)
                        ->nullable(),
                ]),

            Section::make('Pemotongan Pajak')
                ->description('Dihitung otomatis berdasarkan kode rekening DPA. Dapat diubah manual jika perlu.')
                ->schema([
                    TextInput::make('ppn')
                        ->label('PPn (Rp)')
                        ->numeric()
                        ->default(0)
                        ->helperText('Pajak Pertambahan Nilai'),

                    TextInput::make('pph21')
                        ->label('PPh 21 (Rp)')
                        ->numeric()
                        ->default(0)
                        ->helperText('Honorarium / penghasilan orang pribadi'),

                    TextInput::make('pph22')
                        ->label('PPh 22 (Rp)')
                        ->numeric()
                        ->default(0)
                        ->helperText('Pembelian barang'),

                    TextInput::make('pph23')
                        ->label('PPh 23 (Rp)')
                        ->numeric()
                        ->default(0)
                        ->helperText('Jasa / sewa'),

                    Placeholder::make('info_pajak')
                        ->label('Ringkasan Pajak')
                        ->content(function (Get $get): HtmlString {
                            $jumlah = (int) $get('jumlah');
                            $ppn = (int) $get('ppn');
                            $pph21 = (int) $get('pph21');
                            $pph22 = (int) $get('pph22');
                            $pph23 = (int) $get('pph23');
                            $totalPajak = $ppn + $pph21 + $pph22 + $pph23;
                            $bersih = $jumlah - $totalPajak;

                            $fmt = fn ($v) => 'Rp ' . number_format($v, 0, ',', '.');

                            return new HtmlString(
                                "<div class='grid grid-cols-3 gap-3 text-sm'>" .
                                "<div class='bg-gray-50 rounded p-2'><div class='text-xs text-gray-500'>Jumlah Kotor</div><div class='font-semibold'>{$fmt($jumlah)}</div></div>" .
                                "<div class='bg-red-50 rounded p-2'><div class='text-xs text-gray-500'>Total Potongan Pajak</div><div class='font-semibold text-red-700'>{$fmt($totalPajak)}</div></div>" .
                                "<div class='bg-green-50 rounded p-2'><div class='text-xs text-gray-500'>Jumlah Bersih (Diterima)</div><div class='font-semibold text-green-700'>{$fmt($bersih)}</div></div>" .
                                "</div>"
                            );
                        })
                        ->columnSpanFull(),
                ])
                ->columns(4)
                ->collapsible(),
        ]);
    }

    protected static function recalculateTax(?int $rincianId, int $jumlah, Set $set): void
    {
        if (! $rincianId || $jumlah <= 0) {
            return;
        }

        $rincian = DpaRincianBelanja::find($rincianId);
        if (! $rincian) {
            return;
        }

        /** @var TaxCalculationService $svc */
        $svc = app(TaxCalculationService::class);
        $tax = $svc->calculate($jumlah, $rincian->kode_rekening);

        $set('ppn', $tax['ppn']);
        $set('pph21', $tax['pph21']);
        $set('pph22', $tax['pph22']);
        $set('pph23', $tax['pph23']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                Spj::query()
                    ->with(['suratKeluar.requester', 'personil', 'dpaRincianBelanja.subKegiatan'])
            )
            ->defaultSort(function (Builder $query): Builder {
                return $query
                    ->select('spjs.*')
                    ->leftJoin('personils as p_spj', 'spjs.personil_id', '=', 'p_spj.id')
                    ->orderBy('p_spj.jabatan_akronim')
                    ->orderByDesc('spjs.tanggal');
            })
            ->columns([
                Tables\Columns\TextColumn::make('personil.jabatan_akronim')
                    ->label('Seksi')
                    ->formatStateUsing(fn ($state) => $state ? strtoupper($state) : '-')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('personil.nama')
                    ->label('PPTK / Pemohon')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('nomor_kwitansi')
                    ->label('Nomor Kwitansi')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tahun')
                    ->label('Tahun')
                    ->sortable(),

                Tables\Columns\TextColumn::make('uraian')
                    ->label('Uraian')
                    ->limit(50)
                    ->searchable(),

                Tables\Columns\TextColumn::make('jumlah')
                    ->label('Jumlah (Rp)')
                    ->alignRight()
                    ->formatStateUsing(fn ($state) => $state ? number_format((int) $state, 0, ',', '.') : '-'),

                Tables\Columns\TextColumn::make('suratKeluar.nomor_label')
                    ->label('Surat Keluar')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('dpaRincianBelanja.kode_rekening')
                    ->label('Kode Rekening DPA')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('dpaRincianBelanja.subKegiatan.kode')
                    ->label('Sub Kegiatan DPA')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('personil_id')
                    ->label('PPTK / Seksi')
                    ->relationship('personil', 'nama')
                    ->searchable(),

                Tables\Filters\Filter::make('tahun')
                    ->form([
                        TextInput::make('tahun')->label('Tahun')->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['tahun'] ?? null,
                            fn (Builder $q, $tahun) => $q->where('tahun', (int) $tahun)
                        );
                    }),
            ])
            ->actions([
                Action::make('cetak_kwitansi')
                    ->label('Cetak Kwitansi')
                    ->icon('heroicon-o-printer')
                    ->url(fn (Spj $record) => route('spj.kwitansi.cetak', $record))
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn ($record) => blank($record) ? false : (method_exists($record, 'trashed') && $record->trashed())),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->emptyStateHeading('Belum ada data SPJ')
            ->emptyStateDescription('Tambahkan data SPJ menggunakan tombol di atas.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSpjs::route('/'),
            'create' => CreateSpj::route('/create'),
            'edit' => EditSpj::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        if (! $user || ! RoleAccess::canSeeNav($user, 'filament.admin.resources.spj')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $akronim = strtolower(trim((string) ($user->jabatan_akronim ?? '')));

        return $akronim === 'ekbang';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user || ! RoleAccess::canAccessRoute($user, 'filament.admin.resources.spj')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $akronim = strtolower(trim((string) ($user->jabatan_akronim ?? '')));

        return $akronim === 'ekbang';
    }
}
